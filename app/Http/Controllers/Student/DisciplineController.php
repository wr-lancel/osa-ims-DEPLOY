<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\DisciplineWorkflowStep;
use App\Models\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DisciplineController extends Controller
{
    use AuthorizesRequests;
    /**
     * My violations list (own records only by student_number).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        $sortBy = in_array($request->input('sort_by'), ['violation_date', 'created_at', 'severity', 'status']) ? $request->input('sort_by') : 'violation_date';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query = Discipline::with(['enrollment.academicCalendar'])
            ->where('student_number', $studentNumber)
            ->orderBy($sortBy, $sortDir);

        if ($request->filled('acad_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('acad_id', $request->acad_id);
            });
        }

        $violations = $query->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn($v) => [
                'discipline_id' => $v->discipline_id,
                'violation_date' => $v->violation_date->format('Y-m-d'),
                'violation_type' => $v->violation_type,
                'severity' => $v->severity,
                'status' => $v->status,
                'term_label' => $v->enrollment && $v->enrollment->academicCalendar
                    ? $v->enrollment->academicCalendar->display_label
                    : null,
            ]);

        $unreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('is_read', false)
            ->count();

        $complaintUnreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'complaint')
            ->where('is_read', false)
            ->count();

        $terms = \App\Models\AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => ['calendar_id' => $c->calendar_id, 'display_label' => $c->display_label]);

        $codeOfConductSections = $this->getCodeOfConductSectionsWithContent();

        return Inertia::render('Student/Discipline/Index', [
            'violations' => $violations,
            'unreadNotificationsCount' => $unreadCount,
            'complaintUnreadCount' => $complaintUnreadCount,
            'filters' => $request->only(['acad_id', 'sort_by', 'sort_dir']),
            'terms' => $terms,
            'codeOfConductSections' => $codeOfConductSections,
        ]);
    }

    /**
     * Violation detail (own only); mark related notification as read when opened.
     */
    public function show(Discipline $discipline): Response|RedirectResponse
    {
        $this->authorize('view', $discipline);

        $user = Auth::user();
        $discipline->load(['enrollment.academicCalendar', 'meetings', 'disciplineHistories.changedBy']);

        $violation = [
            'discipline_id' => $discipline->discipline_id,
            'violation_date' => $discipline->violation_date->format('Y-m-d'),
            'violation_type' => $discipline->violation_type,
            'description' => $discipline->description,
            'sanction' => $discipline->sanction,
            'date_resolved' => $discipline->date_resolved?->format('Y-m-d'),
            'severity' => $discipline->severity,
            'status' => $discipline->status,
            'remarks' => $discipline->remarks,
            'narrative_report' => $discipline->narrative_report,
            'narrative_report_file_url' => $discipline->narrative_report_file
                ? Storage::url($discipline->narrative_report_file)
                : null,
            'narrative_report_file_name' => $discipline->narrative_report_file
                ? basename($discipline->narrative_report_file)
                : null,
            'term_label' => $discipline->enrollment && $discipline->enrollment->academicCalendar
                ? $discipline->enrollment->academicCalendar->display_label
                : null,
        ];

        $meetings = $discipline->meetings->map(fn($m) => [
            'meeting_id' => $m->meeting_id,
            'meeting_date' => $m->meeting_date->format('Y-m-d'),
            'meeting_time' => $m->meeting_time,
            'location' => $m->location,
            'purpose_notes' => $m->purpose_notes,
            'status' => $m->status,
        ]);

        $history = $discipline->disciplineHistories->map(fn($h) => [
            'old_status' => $h->old_status,
            'new_status' => $h->new_status,
            'note' => $h->note,
            'created_at' => $h->created_at?->format('Y-m-d H:i'),
        ]);

        Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('related_case_id', $discipline->discipline_id)
            ->update(['is_read' => true]);

        return Inertia::render('Student/Discipline/Show', [
            'violation' => $violation,
            'meetings' => $meetings,
            'history' => $history,
            'workflowSteps' => DisciplineWorkflowStep::getStepsForProgressBar(),
            'terminalStatuses' => DisciplineWorkflowStep::getTerminalNames(),
        ]);
    }

    /**
     * Discipline notifications list.
     */
    public function notifications(Request $request): Response
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        $notifications->getCollection()->transform(function ($n) {
            return [
                'notification_id' => $n->notification_id,
                'title' => $n->title,
                'message' => $n->message,
                'related_case_id' => $n->related_case_id,
                'related_meeting_id' => $n->related_meeting_id,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ];
        });

        $unreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('is_read', false)
            ->count();

        return Inertia::render('Student/Discipline/Notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark notification(s) as read.
     */
    public function markNotificationRead(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $id = $request->input('notification_id');
        $ids = $request->input('notification_ids', $id ? [$id] : []);

        if (!empty($ids)) {
            Notification::where('user_id', $user->user_id)
                ->where('type', 'discipline')
                ->whereIn('notification_id', $ids)
                ->update(['is_read' => true]);
        }

        return redirect()->back()->with('success', 'Marked as read.');
    }

    /**
     * Code of Conduct list (static categories).
     */
    public function codeOfConductIndex(): Response
    {
        $sections = $this->getCodeOfConductSections();

        return Inertia::render('Student/Discipline/CodeOfConduct/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Code of Conduct topic detail.
     */
    public function codeOfConductShow(string $slug): Response
    {
        $topics = $this->getCodeOfConductTopics();
        $topic = $topics[$slug] ?? [
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'severity' => 'info',
            'content' => 'This section contains important information about the student code of conduct. Content will be updated by the administration.',
        ];

        return Inertia::render('Student/Discipline/CodeOfConduct/Show', [
            'slug' => $slug,
            'title' => $topic['title'],
            'severity' => $topic['severity'],
            'content' => $topic['content'],
        ]);
    }

    /**
     * Section definitions for Index page.
     */
    private function getCodeOfConductSections(): array
    {
        return [
            [
                'id' => 'academic-integrity',
                'title' => 'Academic Integrity',
                'items' => [
                    ['slug' => 'plagiarism', 'title' => 'Plagiarism'],
                    ['slug' => 'cheating', 'title' => 'Cheating'],
                    ['slug' => 'fabrication', 'title' => 'Fabrication Of Data'],
                    ['slug' => 'deception', 'title' => 'Deception'],
                ],
            ],
            [
                'id' => 'conduct-and-behavior',
                'title' => 'Conduct And Behavior',
                'items' => [
                    ['slug' => 'bullying-and-harassment', 'title' => 'Bullying And Harassment'],
                    ['slug' => 'cyber-bullying', 'title' => 'Cyber Bullying'],
                    ['slug' => 'discrimination', 'title' => 'Discrimination'],
                    ['slug' => 'fighting-and-violence', 'title' => 'Fighting And Physical Violence'],
                    ['slug' => 'insubordination', 'title' => 'Insubordination'],
                    ['slug' => 'disorder-and-misconduct', 'title' => 'Disorder And Misconduct'],
                ],
            ],
            [
                'id' => 'prohibited-activities',
                'title' => 'Prohibited Activities',
                'items' => [
                    ['slug' => 'substance-use', 'title' => 'Substance Use (Drugs And Alcohol)'],
                    ['slug' => 'smoking', 'title' => 'Smoking'],
                    ['slug' => 'gambling', 'title' => 'Gambling'],
                    ['slug' => 'pornographic-materials', 'title' => 'Pornographic Materials'],
                    ['slug' => 'moral-turpitude', 'title' => 'Acts Involving Moral Turpitude'],
                ],
            ],
            [
                'id' => 'property-and-security',
                'title' => 'Property And Security',
                'items' => [
                    ['slug' => 'vandalism', 'title' => 'Vandalism And Property Destruction'],
                    ['slug' => 'unauthorized-entry', 'title' => 'Unauthorized Entry And Trespassing'],
                    ['slug' => 'computer-misuse', 'title' => 'Misuse Of Computer Systems'],
                    ['slug' => 'weapons-and-explosives', 'title' => 'Weapons And Explosives'],
                    ['slug' => 'theft', 'title' => 'Stealing And Theft'],
                ],
            ],
            [
                'id' => 'campus-rules',
                'title' => 'Campus Rules',
                'items' => [
                    ['slug' => 'dress-code', 'title' => 'Dress Code Policy'],
                    ['slug' => 'id-card-policy', 'title' => 'ID Card Policy'],
                    ['slug' => 'attendance', 'title' => 'Attendance And Punctuality'],
                    ['slug' => 'cellphone-use', 'title' => 'Use Of Cellular Phones'],
                ],
            ],
            [
                'id' => 'special-laws',
                'title' => 'Offenses Under Special Laws',
                'items' => [
                    ['slug' => 'anti-hazing', 'title' => 'Anti-Hazing Law'],
                    ['slug' => 'anti-bullying-act', 'title' => 'Anti-Bullying Act Of 2013'],
                    ['slug' => 'anti-sexual-harassment', 'title' => 'Anti-Sexual Harassment'],
                    ['slug' => 'cybercrime-prevention', 'title' => 'Cybercrime Prevention Act'],
                    ['slug' => 'safe-spaces-act', 'title' => 'Safe Spaces Act (Anti-Bastos Law)'],
                    ['slug' => 'dangerous-drugs-act', 'title' => 'Comprehensive Dangerous Drugs Act'],
                    ['slug' => 'vawc', 'title' => 'Anti-Violence Against Women And Their Children Act'],
                ],
            ],
            [
                'id' => 'sanctions-and-process',
                'title' => 'Sanctions And Process',
                'items' => [
                    ['slug' => 'sanctions', 'title' => 'Sanctions And Consequences'],
                    ['slug' => 'appeals', 'title' => 'Appeals Process'],
                ],
            ],
        ];
    }

    /**
     * Get Code of Conduct topics with detailed content from Student Handbook.
     */
    private function getCodeOfConductTopics(): array
    {
        return [
            // ── Academic Integrity ──
            'plagiarism' => [
                'title' => 'Plagiarism',
                'severity' => 'major',
                'content' => "Plagiarism is classified as a Major Offense under the Student Handbook.\n\nPlagiarism is defined as passing as one's own work any assigned report, term paper, case study, analysis, reaction paper and the like, when copied from another source without proper attribution.\n\nThis includes but is not limited to:\n• Submitting someone else's work as your own\n• Copying text from books, websites, or other sources without citation\n• Paraphrasing another's ideas without giving credit\n• Submitting work previously submitted for another course without permission\n\nStudents must ensure that all work submitted is original and properly attributed. Any form of plagiarism will be subject to major disciplinary sanctions.",
            ],
            'cheating' => [
                'title' => 'Cheating',
                'severity' => 'major',
                'content' => "Cheating is classified as a Major Offense under the Student Handbook.\n\nCheating in online or offline examinations and/or quizzes includes but is not limited to the following:\n• Deliberately looking at a neighbor's examination papers\n• Copying from, or allowing another student to copy from one's examination papers\n• Having somebody else take the examination for another student\n• Communicating verbally and non-verbally without permission during examinations\n• Using or bringing out cellular phones or any communication gadget during examinations or quizzes\n\nAll forms of cheating are considered acts of dishonesty and will be subject to major disciplinary sanctions as outlined in the Student Handbook.",
            ],
            'fabrication' => [
                'title' => 'Fabrication Of Data',
                'severity' => 'major',
                'content' => "Fabrication is classified as a Major Offense under the Student Handbook.\n\nFabrication is defined as the submission of falsified data, information, citations, sources, or results in any academic exercise.\n\nThis includes but is not limited to:\n• Inventing or altering data in a lab report or research paper\n• Listing sources in a bibliography that were not actually used\n• Falsifying information in any academic document\n• Submitting falsified results or findings\n\nFabrication of data undermines academic integrity and the trust placed in scholarly work. Violations will be subject to major disciplinary sanctions.",
            ],
            'deception' => [
                'title' => 'Deception',
                'severity' => 'major',
                'content' => "Deception is classified as a Major Offense under the Student Handbook.\n\nDeception is defined as providing false information to a faculty member regarding a formal academic activity or requirement.\n\nThis includes but is not limited to:\n• Giving a false reason for missing a deadline or examination\n• Providing fabricated documentation (e.g., fake medical certificates)\n• Misrepresenting circumstances to gain an academic advantage\n• Providing false statements to any institutional administrator or official\n\nAll forms of deception are taken seriously and will result in major disciplinary action.",
            ],

            // ── Conduct and Behavior ──
            'bullying-and-harassment' => [
                'title' => 'Bullying And Harassment',
                'severity' => 'major',
                'content' => "Bullying and Harassment are classified as Major Offenses under the Student Handbook.\n\nThe institution maintains a zero-tolerance policy for all forms of harassment which tends to put any member of the students, faculty, administration or staff in a hostile environment, including those that transgress gender equality and sensitivity and other similar forms of discrimination.\n\nThis includes:\n• All forms of harassment directed at any member of the institution\n• Oppression or threatening behavior — resorting to physical force or violence to settle disputes\n• Intimidating and humiliating forms of abuse (hurtful, imitating posts, images, videos, texts, emails/messages, social media)\n• Direct assault, either verbal or physical, upon any student, faculty, administrative or non-teaching staff\n• Willful suppression of another person's rights\n\nAny violative act expressed under R.A. 9442 (Prohibitions on Verbal, Non-Verbal Ridicule Against Persons with Disability) is also covered.\n\nViolations will result in major disciplinary sanctions.",
            ],
            'cyber-bullying' => [
                'title' => 'Cyber Bullying',
                'severity' => 'major',
                'content' => "Cyber Bullying is classified as a Major Offense under the Student Handbook.\n\nCyber Bullying refers to bullying conducted through digital means, including any forms of abuse through electronic media.\n\nThis includes but is not limited to:\n• Intimidating and humiliating posts, images, and videos shared online\n• Sending hurtful, abusive, or threatening texts, emails, or messages\n• Social media harassment or defamation\n• Creating fake accounts to harass or impersonate others\n• Repeatedly sending unwanted emails or messages to individuals\n• Abusive, harassing, or threatening behavior online\n• Stalking behavior through digital platforms\n\nCyber bullying may also fall under the Cybercrime Prevention Act and the Anti-Bullying Act of 2013. Violations will result in major disciplinary sanctions and may be referred to proper authorities.",
            ],
            'discrimination' => [
                'title' => 'Discrimination',
                'severity' => 'major',
                'content' => "Discrimination is classified as a Major Offense under the Student Handbook.\n\nDiscrimination on the basis of the following is strictly prohibited:\n• Race\n• Color\n• Religion\n• National origin\n• Sex\n• Age\n• Disability\n• Veteran status\n• Sexual orientation\n• Gender identity\n\nAll students have the right to equal treatment and a safe learning environment regardless of their background or identity. Any form of discrimination, whether by law or any similar acts that put a person at a disadvantage based on the above categories, is subject to major disciplinary sanctions.\n\nWhere such discrimination is required or excluded by law, it is the only exception permitted.",
            ],
            'fighting-and-violence' => [
                'title' => 'Fighting And Physical Violence',
                'severity' => 'major',
                'content' => "Fighting and Physical Violence are classified as Major Offenses under the Student Handbook.\n\nThe following acts are strictly prohibited:\n• Fighting on or off campus premises\n• Direct assault, either verbal or physical, upon any student, faculty, administrative or non-teaching staff\n• Oppression or resorting to physical force or violence to settle disputes\n• Threatening another person's physical safety, honor, or property\n\nStudents involved in physical altercations will face major disciplinary sanctions regardless of who initiated the conflict. Students are expected to resolve disputes peacefully and may seek assistance from faculty, counselors, or the Student Discipline Office.",
            ],
            'insubordination' => [
                'title' => 'Insubordination',
                'severity' => 'major',
                'content' => "Insubordination is classified as a Major Offense under the Student Handbook.\n\nInsubordination is defined as willful disobedience to any just order of any person in authority within the College premises.\n\nThis includes but is not limited to:\n• Willful disregard of institution rules, regulations, announcements, and the like posted on bulletin boards, displayed on streamers/tarpaulins or posted online\n• Willful refusal to pay just debts\n• Tampering with official notices, announcements, and similar materials\n• Failure to comply with summons or sanctions imposed under the Student Discipline Manual\n• Misrepresenting information before the Institution Discipline Committee\n\nStudents are expected to respect and follow the lawful directives of institution officials and faculty members.",
            ],
            'disorder-and-misconduct' => [
                'title' => 'Disorder And Misconduct',
                'severity' => 'mixed',
                'content' => "Disorder and Misconduct may be classified as Minor or Major Offenses depending on severity.\n\nMinor Offenses:\n• Simple misconduct or misbehavior during institution/college/department programs, activities, events, or competitions\n• Disrupting or disturbance of academic and non-academic functions (class, organization activities, academic activities, co-curricular activities)\n• Littering\n• Loitering\n\nMajor Offenses:\n• Deliberate disruption of an academic function or school activity which tends to create disorder or tumult\n• Acts of disorder, lewdness, or obscene conduct and acts of intimacy within the institution's premises and during on and off-campus academic and co-curricular activities\n• Acts that bring the name of the institution or any of its members into disrepute\n• Conduct prejudicial to the best interest of the institution\n\nThe severity of the sanction depends on the nature and impact of the misconduct.",
            ],

            // ── Prohibited Activities ──
            'substance-use' => [
                'title' => 'Substance Use (Drugs And Alcohol)',
                'severity' => 'major',
                'content' => "Substance Use is classified as a Major Offense under the Student Handbook.\n\nThe following are strictly prohibited:\n• Unauthorized or illegal possession or use of prohibited drugs including but not limited to marijuana, shabu, heroin, rugby, and hallucinogen drugs or substances in any form within the institution premises or during activities\n• Possession of any regulated drug without proper prescription\n• Possession and/or bringing or drinking of alcoholic beverages within the college premises or attending on campus and off-campus activities in a state of intoxication\n\nViolations related to substance use are also covered under the Comprehensive Dangerous Drugs Act (R.A. 9165) and may be referred to law enforcement authorities in addition to institutional disciplinary sanctions.",
            ],
            'smoking' => [
                'title' => 'Smoking',
                'severity' => 'minor',
                'content' => "Smoking is classified as a Minor Offense under the Student Handbook.\n\nSmoking within the Institution or outside of the Institution within 100 meters is prohibited, especially if classes are suspended in the conduct of such activity.\n\nThis is pursuant to Section 10 of R.A. 9211 (Tobacco Regulation Act of 2003).\n\nStudents found smoking within the prohibited areas will face minor disciplinary sanctions. Repeated violations may escalate to more severe sanctions.",
            ],
            'gambling' => [
                'title' => 'Gambling',
                'severity' => 'major',
                'content' => "Gambling is classified as a Major Offense under the Student Handbook.\n\nGambling of any form within the premises or during off-campus academic or co-curricular activities is strictly prohibited.\n\nThis includes but is not limited to:\n• Card games or any games of chance played for money or stakes\n• Online gambling conducted using institution facilities or during school activities\n• Organizing or participating in any form of betting within the campus\n\nStudents found engaging in gambling activities will face major disciplinary sanctions.",
            ],
            'pornographic-materials' => [
                'title' => 'Pornographic Materials',
                'severity' => 'major',
                'content' => "Possession or distribution of pornographic materials is classified as a Major Offense under the Student Handbook.\n\nThe following are strictly prohibited:\n• Accessing pornographic materials using institution facilities or within the premises\n• Possessing pornographic materials within the campus\n• Displaying and distributing pornographic materials within and during on/off campus activities\n\nThis offense falls under offenses against public morals, which includes disgraceful, immoral, fraudulent, and/or unlawful conduct. Violations will result in major disciplinary sanctions.",
            ],
            'moral-turpitude' => [
                'title' => 'Acts Involving Moral Turpitude',
                'severity' => 'major',
                'content' => "Acts involving moral turpitude are classified as Major Offenses under the Student Handbook.\n\nMoral turpitude refers to any act done within or outside the premises of the college, those which are done contrary to justice, modesty, or good morals; an act of baseness, vileness, or depravity in the private and social duties which a man owes his fellowmen, or to society in general.\n\nThis also includes:\n• Illicit relationships (relationship with married individuals, with member of the academic and non-academic staff)\n• Disgraceful, immoral, fraudulent, and/or unlawful conduct\n\nOffenses under this category are subject to the most severe disciplinary sanctions.",
            ],

            // ── Property and Security ──
            'vandalism' => [
                'title' => 'Vandalism And Property Destruction',
                'severity' => 'major',
                'content' => "Vandalism is classified as a Major Offense under the Student Handbook.\n\nThe following acts are strictly prohibited:\n• Vandalism or destruction of public or institution property\n• Damage to facilities, equipment, or infrastructure belonging to the institution\n• Defacing walls, furniture, or any institution property\n• Appropriating institution properties for personal use\n\nStudents found responsible for vandalism will face major disciplinary sanctions and may be required to pay for the cost of repair or replacement of damaged property.",
            ],
            'unauthorized-entry' => [
                'title' => 'Unauthorized Entry And Trespassing',
                'severity' => 'major',
                'content' => "Unauthorized Entry and Trespassing are classified as Major Offenses under the Student Handbook.\n\nThe following acts are prohibited:\n• Unauthorized entry (whether forcible or otherwise) to any building, structure, construction site, or facility including an individual's room\n• Unauthorized entry to or use of institution grounds and off-campus activities\n• Entering restricted areas without proper authorization\n• Trespassing on institution property outside of authorized hours\n\nStudents must only access areas and facilities they are authorized to use. Violations will result in major disciplinary sanctions.",
            ],
            'computer-misuse' => [
                'title' => 'Misuse Of Computer Systems',
                'severity' => 'major',
                'content' => "Misuse of Computer Systems is classified as a Major Offense under the Student Handbook.\n\nMisuse or abuse of Computer Facilities or Information and Communication Systems (as defined under R.A. 8792) includes but is not limited to:\n• Hacking or cracking a computer system/server\n• Unauthorized entry into a file to use, read, or change the contents, or for any other purpose\n• Use of institution facilities for unauthorized purposes\n• Abusive, harassing, or threatening behavior through digital means\n• Stalking behavior or repeatedly sending unwanted emails or messages to individuals\n\nViolations may also fall under the Cybercrime Prevention Act and will result in major disciplinary sanctions. Cases may be referred to proper authorities.",
            ],
            'weapons-and-explosives' => [
                'title' => 'Weapons And Explosives',
                'severity' => 'major',
                'content' => "Possession of weapons or explosives is classified as a Major Offense under the Student Handbook.\n\nThe following are strictly prohibited within the premises of the institution:\n• Carrying or possession of firearms\n• Possession of deadly weapons such as but not limited to blades of any kind, ice picks, and similar items\n• Possession of explosives of any kind\n\nThis offense is treated with the utmost severity. Students found in possession of weapons or explosives will face major disciplinary sanctions and the case will be referred to law enforcement authorities.",
            ],
            'theft' => [
                'title' => 'Stealing And Theft',
                'severity' => 'major',
                'content' => "Stealing is classified as a Major Offense under the Student Handbook.\n\nThis includes:\n• Stealing or any attempt thereof\n• Taking property belonging to the institution, other students, faculty, or staff without permission\n• Any act of theft conducted within or outside the campus during school-related activities\n\nStudents found guilty of stealing will face major disciplinary sanctions and may be required to return or compensate for the stolen property. Cases may also be referred to proper authorities.",
            ],

            // ── Campus Rules ──
            'dress-code' => [
                'title' => 'Dress Code Policy',
                'severity' => 'minor',
                'content' => "Dress Code violations are classified as Minor Offenses under the Student Handbook.\n\nImproper or inappropriate use of school uniform or dress code is prohibited.\n\nAmong Female Students:\n• No colored hair (blonde, green, red, brown, highlights, hair extensions)\n• No skirts above the knee — must be two (2) inches below the knee\n• No multiple earrings\n• No leggings, jeggings, and tattered jeans\n\nAmong Male Students:\n• No colored hair and long hair (clean-cut/barber's cut required)\n• No earrings\n• No cigarettes and e-cigars\n• No sideburns, mustache, and beard\n\nAdditional Prohibitions for All Students:\n• Clothing with images/pictures of prohibited substances, items, and the like\n• Clothing with indecent, provocative, and violent language in print or text\n• Sleeveless shirts and shorts of all kinds\n• Tight-fitting, ripped, or tattered clothing and pants\n• Slippers, sandals, and flip-flops\n• Visible undergarments\n• Cross dressing\n• Colored hair\n• Tattoos must be covered or concealed\n\nRepeated violations may result in escalated sanctions.",
            ],
            'id-card-policy' => [
                'title' => 'ID Card Policy',
                'severity' => 'minor',
                'content' => "ID Card violations are classified as Minor Offenses under the Student Handbook.\n\nMinor Offenses:\n• Inappropriate or non-wearing of the Institutions Identification (ID) Card\n• Lending to or borrowing from a fellow bona fide student any Institutions Identification (ID) Card\n\nMajor Offense:\n• Lending an ID Card to a non-bona fide student of any institution\n\nStudents are required to wear their ID Cards at all times while within the institution premises. The ID Card serves as proof of enrollment and is essential for security purposes.",
            ],
            'attendance' => [
                'title' => 'Attendance And Punctuality',
                'severity' => 'minor',
                'content' => "Attendance-related violations are classified as Minor Offenses under the Student Handbook.\n\nThe following are considered violations:\n• Non-participation in activities whereby attendance is required, especially if classes are suspended in the conduct of such activity\n• Habitual tardiness or absenteeism without valid reason\n\nRegular attendance and punctuality are expected of all students. Excused absences must be properly documented and communicated to the relevant faculty or office.\n\nRepeated violations may result in escalated disciplinary sanctions.",
            ],
            'cellphone-use' => [
                'title' => 'Use Of Cellular Phones',
                'severity' => 'minor',
                'content' => "Unauthorized use of cellular phones is classified as a Minor Offense under the Student Handbook.\n\nMinor Offense:\n• Use of cellular phones or any similar communication/electronic gadget during class hours\n\nMajor Offense:\n• Using or bringing out cellular phones or any communication gadget during examinations or quizzes, whether or not the student uses them for cheating\n\nStudents should keep their phones on silent mode and stored during class. Using phones during examinations is treated as a major offense related to academic dishonesty.",
            ],

            // ── Offenses Under Special Laws ──
            'anti-hazing' => [
                'title' => 'Anti-Hazing Law',
                'severity' => 'major',
                'content' => "Offenses under the Anti-Hazing Law are classified as Major Offenses under the Student Handbook.\n\nHazing refers to any act that results in physical or psychological suffering, harm, or injury inflicted on a recruit, neophyte, applicant, or member as part of an initiation rite or practice.\n\nThe Anti-Hazing Law (R.A. 11053) strictly prohibits all forms of hazing whether inside or outside the school premises. This includes:\n• Physical hazing — any form of physical abuse or violence\n• Psychological hazing — any act that causes mental suffering, embarrassment, or humiliation\n• Any initiation rite that endangers the life or safety of a person\n\nViolations are subject to the most severe institutional disciplinary sanctions and criminal prosecution under Philippine law.",
            ],
            'anti-bullying-act' => [
                'title' => 'Anti-Bullying Act Of 2013',
                'severity' => 'major',
                'content' => "Offenses under the Anti-Bullying Act of 2013 (R.A. 10627) are classified as Major Offenses under the Student Handbook.\n\nBullying refers to any severe or repeated use of written, verbal, or electronic expression, or physical act or gesture directed at another student that has the effect of:\n• Causing physical or emotional harm or damage to property\n• Placing another student in reasonable fear of physical or emotional harm\n• Creating a hostile environment at school\n• Infringing on the rights of another student at school\n\nThe institution is committed to implementing anti-bullying policies and providing a safe learning environment for all students. All reported incidents will be investigated and addressed accordingly.",
            ],
            'anti-sexual-harassment' => [
                'title' => 'Anti-Sexual Harassment',
                'severity' => 'major',
                'content' => "Offenses under the Anti-Sexual Harassment Law are classified as Major Offenses under the Student Handbook.\n\nSexual harassment in an educational institution occurs when:\n• A teacher, instructor, professor, coach, trainor, or any person in authority demands, requests, or requires any sexual favor from a student\n• Any act of sexual harassment between students or any member of the academic community\n\nThis includes unwelcome sexual advances, requests for sexual favors, and other verbal or physical conduct of a sexual nature.\n\nThe institution strictly enforces the Anti-Sexual Harassment Act (R.A. 7877) and related laws. Violations will result in major disciplinary sanctions and may be referred to law enforcement.",
            ],
            'cybercrime-prevention' => [
                'title' => 'Cybercrime Prevention Act',
                'severity' => 'major',
                'content' => "Offenses under the Cybercrime Prevention Act (R.A. 10175) are classified as Major Offenses under the Student Handbook.\n\nThis includes but is not limited to:\n• Illegal access to computer systems or servers\n• Data interference — unauthorized alteration or destruction of data\n• System interference — hindering the functioning of a computer system\n• Cyber-squatting and identity theft online\n• Cybersex and online exploitation\n• Online libel and defamation\n• Computer-related fraud and forgery\n\nStudents who commit cybercrimes face both institutional disciplinary sanctions and criminal prosecution under Philippine law.",
            ],
            'safe-spaces-act' => [
                'title' => 'Safe Spaces Act (Anti-Bastos Law)',
                'severity' => 'major',
                'content' => "Offenses under the Safe Spaces Act (R.A. 11313), also known as the Anti-Bastos Law, are classified as Major Offenses under the Student Handbook.\n\nThe Safe Spaces Act prohibits gender-based sexual harassment in streets, public spaces, online, workplaces, and educational institutions.\n\nProhibited acts include:\n• Catcalling, wolf-whistling, and unwanted sexual remarks\n• Persistent uninvited comments or gestures on a person's appearance\n• Stalking, whether physical or online\n• Unauthorized recording or sharing of a person's photo or video, especially of a sexual nature\n• Online sexual harassment including sending unsolicited sexual content\n\nThe institution is committed to maintaining safe spaces for all students. Violations will result in major disciplinary sanctions and may be referred to proper authorities.",
            ],
            'dangerous-drugs-act' => [
                'title' => 'Comprehensive Dangerous Drugs Act',
                'severity' => 'major',
                'content' => "Offenses under the Comprehensive Dangerous Drugs Act (R.A. 9165) are classified as Major Offenses under the Student Handbook.\n\nThe following are strictly prohibited:\n• Use, possession, or distribution of dangerous drugs in any form\n• Possession of drug paraphernalia\n• Being under the influence of dangerous drugs within the campus or during school activities\n• Selling, trading, or delivering dangerous drugs\n\nDangerous drugs include but are not limited to: marijuana, shabu (methamphetamine), heroin, cocaine, ecstasy, and other regulated or prohibited substances.\n\nViolations carry the most severe institutional sanctions and will be referred to law enforcement authorities for criminal prosecution.",
            ],
            'vawc' => [
                'title' => 'Anti-Violence Against Women And Their Children Act',
                'severity' => 'major',
                'content' => "Offenses under the Anti-Violence Against Women and Their Children Act (R.A. 9262) are classified as Major Offenses under the Student Handbook.\n\nThis law protects women and their children from violence committed by any person. Prohibited acts include:\n• Physical violence — acts that include bodily or physical harm\n• Sexual violence — acts of a sexual nature committed against a woman or her child\n• Psychological violence — acts or omissions causing mental or emotional suffering\n• Economic abuse — acts that make a woman financially dependent or controlling her financial resources\n\nThe institution takes all reports of VAWC seriously. Violations will result in major disciplinary sanctions and will be referred to law enforcement and social services.",
            ],

            // ── Sanctions and Process ──
            'sanctions' => [
                'title' => 'Sanctions And Consequences',
                'severity' => 'info',
                'content' => "The institution imposes the following sanctions based on the severity and frequency of offenses:\n\nMinor Offense Sanctions:\n\n1st Minor Offense:\n• Verbal reprimand/warning with record at the Student Discipline Office and Guidance Office\n• Non-issuance of good moral certificate for three (3) months from the date of finality of the decision\n• Subject to suspension under major offense: 1 day to 2 weeks\n\n2nd Minor Offense:\n• Written reprimand and promissory note with record at the Student Discipline Office\n• Non-issuance of good moral certificate for one (1) semester from the date of finality of the decision\n• Subject to suspension under major offense: 2 weeks to 1 month\n\n3rd Minor Offense:\n• Suspension of not exceeding 1 semester\n\nMajor Offense Sanctions:\n\n1st Major Offense:\n• Minimum of fifteen (15) school days' suspension to one (1) semester\n\n2nd Major Offense:\n• Suspension of one (1) semester or more, up to expulsion depending on the gravity of the offense\n\nNote: Sanctions may vary depending on the specific circumstances of the offense and as determined by the Institution Discipline Committee.",
            ],
            'appeals' => [
                'title' => 'Appeals Process',
                'severity' => 'info',
                'content' => "Students and parents/guardians may appeal disciplinary decisions according to the process outlined in the Student Handbook.\n\nKey points about the appeals process:\n• Appeals must be filed within the prescribed period after receiving the decision\n• The appeal must be in writing and addressed to the appropriate authority\n• The appeal should clearly state the grounds for the appeal\n• The Institution Discipline Committee will review the appeal and render a decision\n\nDuring the appeals process:\n• Students are expected to comply with any interim measures imposed\n• The original decision remains in effect unless otherwise ordered\n• Students have the right to present additional evidence or witnesses\n• The decision of the appeals body is final and binding\n\nStudents are encouraged to seek guidance from the Student Discipline Office regarding the appeals process.",
            ],
        ];
    }

    /**
     * Get Code of Conduct sections with full content for inline display.
     */
    private function getCodeOfConductSectionsWithContent(): array
    {
        $topics = $this->getCodeOfConductTopics();
        $sections = $this->getCodeOfConductSections();

        return array_map(function ($section) use ($topics) {
            $section['items'] = array_map(function ($item) use ($topics) {
                $topic = $topics[$item['slug']] ?? ['content' => '', 'severity' => 'info'];
                $item['content'] = $topic['content'];
                $item['severity'] = $topic['severity'];
                return $item;
            }, $section['items']);
            return $section;
        }, $sections);
    }
}
