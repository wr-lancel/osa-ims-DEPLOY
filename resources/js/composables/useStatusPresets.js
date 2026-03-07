/**
 * Status step presets for StatusProgressBar component.
 * Use with: <StatusProgressBar :steps="STATUS_PRESETS.violation" :current-status="violation.status" />
 */
export const STATUS_PRESETS = {
    violation: [
        { value: 'pending', label: 'Pending' },
        { value: 'under investigation', label: 'Under Investigation' },
        { value: 'resolved', label: 'Resolved' },
    ],
    sportsBorrowing: [
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Approved' },
        { value: 'borrowed', label: 'Borrowed' },
        { value: 'returned', label: 'Returned' },
    ],
    sportsBorrowingTerminal: ['rejected', 'overdue'],
    guidanceCase: [
        { value: 'pending', label: 'Pending' },
        { value: 'ongoing', label: 'Ongoing' },
        { value: 'resolved', label: 'Resolved' },
        { value: 'closed', label: 'Closed' },
    ],
    guidanceAppointment: [
        { value: 'pending', label: 'Pending' },
        { value: 'approved', label: 'Approved' },
        { value: 'completed', label: 'Completed' },
    ],
    guidanceAppointmentTerminal: ['rejected', 'cancelled'],
    candidacy: [
        { value: 'submitted', label: 'Submitted' },
        { value: 'under_review', label: 'Under Review' },
        { value: 'approved', label: 'Approved' },
    ],
    candidacyTerminal: ['rejected'],
};
