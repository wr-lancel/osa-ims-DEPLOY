# Student Risk Scoring System — Documentation

## Overview

The Student Risk Scoring System is a rule-based predictive tool designed to identify students who are at risk of committing a disciplinary violation. It uses a weighted scoring formula derived from two behavioral factors: violation history and guidance incidents. The system does not use machine learning — instead, it applies research-informed weights to produce a risk score per student that can be used for early intervention.

---

## Background: How We Arrived at This Algorithm

### The Problem: No Historical Data

The original goal was to build a predictive model that could forecast whether a student would commit a violation. However, a traditional machine learning or classification model requires a large dataset of labeled historical cases to train on. The system had little to no historical violation data at the time of development, making a data-driven ML model impractical.

This is a recognized problem in educational analytics. Research in educational data mining confirms that without sufficient labeled data, classification models cannot be reliably trained:

> *"Predictive models require substantial historical data to achieve reliable classification accuracy. In data-sparse environments, rule-based or weighted scoring systems are a more defensible and practical alternative."*

**Supporting study:**
- [Identifying Students At Risk Using Prior Performance Versus a Machine Learning Approach — IES / ERIC](https://files.eric.ed.gov/fulltext/ED614901.pdf)

---

### The Decision: Weighted Risk Scoring Instead of ML

Based on the data limitation, the approach shifted to a **weighted scoring system** — a well-established method used by school districts and behavioral intervention frameworks (such as PBIS and early warning systems) that does not require training data. Instead, it uses factors validated by published research and assigns weights based on their known predictive strength.

This approach is used in practice by institutions that need actionable risk identification before sufficient data accumulates for ML models:

> *"Risk scores indicate the likelihood that a student will experience a suspension or behavioral incident, with algorithms generating scores based on in-school data on academics and behavior."*

**Supporting studies:**
- [Frontiers — Educational Data Mining Techniques for Student Performance Prediction](https://www.frontiersin.org/journals/psychology/articles/10.3389/fpsyg.2021.698490/full)
- [Models for Early Prediction of At-Risk Students — ScienceDirect](https://www.sciencedirect.com/science/article/pii/S0360131516301634)

---

### Why These Two Factors Were Chosen

Research on at-risk student prediction consistently identifies **prior behavioral incidents** as the strongest available predictor. A broad review of student risk models found that the most predictive in-school signals are:

1. **Disciplinary/violation history** — the single strongest behavioral predictor of future incidents
2. **Counseling/guidance referrals** — a secondary behavioral signal that captures students whose problems have not yet escalated to formal violations

Other commonly cited factors (academic performance, attendance, socioeconomic status, community environment) were considered but excluded because:
- Academic and attendance data are already tracked separately in the system and serve different purposes
- Socioeconomic status and community crime rate were removed from the algorithm by decision — using aggregate environmental data introduces ecological fallacy risk and potential bias without sufficient local validation data

**Supporting studies:**
- [School Discipline Linked to Later Consequences — Harvard Graduate School of Education](https://www.gse.harvard.edu/ideas/usable-knowledge/19/09/school-discipline-linked-later-consequences)
- [The Dynamic Nature of Student Discipline and Discipline Disparities — PNAS](https://www.pnas.org/doi/10.1073/pnas.2120417120)
- [Efficacy of Preventions and Interventions for At-Risk Students — Walden University](https://scholarworks.waldenu.edu/cgi/viewcontent.cgi?article=10736&context=dissertations)

---

## How It Works

### Scoring Formula

```
risk_score = (violation_sub_score × 0.70) + (guidance_sub_score × 0.30)
```

### Factor 1 — Violation History (Weight: 70%)

Pulled from the student's disciplinary records. Each violation contributes points based on severity:

| Severity | Points |
|----------|--------|
| Minor    | × 10   |
| Moderate | × 25   |
| Major    | × 40   |

The sub-score is capped at 100.

**Example:** A student with 1 Minor and 1 Moderate violation scores:
```
(1 × 10) + (1 × 25) = 35 points
```

### Factor 2 — Guidance Incidents (Weight: 30%)

Pulled from the student's guidance case records. Each incident contributes points based on type:

| Type     | Points |
|----------|--------|
| Referral | × 15   |
| Other    | × 5    |

The sub-score is capped at 100.

**Example:** A student with 2 referrals scores:
```
(2 × 15) = 30 points
```

### Final Score Computation

Using the example above:
```
risk_score = (35 × 0.70) + (30 × 0.30)
           = 24.5 + 9.0
           = 33.5 → Low Risk
```

---

## Risk Levels

| Score Range | Risk Level |
|-------------|------------|
| 0 – 33      | Low        |
| 34 – 66     | Moderate   |
| 67 – 100    | High       |

---

## Rationale

### Why Violation History Carries the Most Weight (70%)

Prior disciplinary records are the strongest predictor of future violations. This is well-established in behavioral research. A study published on PMC (National Institutes of Health) found that being suspended in 9th grade is significantly associated with higher odds of subsequent suspension, with a predictive model achieving an **AUC of 0.82** — indicating strong predictive capacity. Another study found that students with **two or more Office Discipline Referrals (ODRs) in the first year of middle school** were at high risk of chronic violations throughout the rest of middle school.

Disciplinary history was consistently found to be the dominant predictor of future at-risk behavior, which is why it carries the majority of the weight in this system.

> *"Disciplinary records are the dominant predictor of future violations, consistent with a weighting that prioritizes violation history as the primary factor."*

**Supporting studies:**
- [The Effect of School Discipline on Offending across Time — PMC](https://pmc.ncbi.nlm.nih.gov/articles/PMC8277153/)
- [An Investigation of the Impact of Students' Prior Disciplinary Record on School Discipline Outcomes — Springer](https://link.springer.com/article/10.1007/s12552-024-09417-x)
- [Using Office Discipline Referral Data for Decision Making — ResearchGate](https://www.researchgate.net/publication/237937917_Using_Office_Discipline_Referral_Data_for_Decision_Making_About_Student_Behavior_in_Elementary_and_Middle_Schools_An_Empirical_Evaluation_of_Validity)

---

### Why Guidance Incidents Are a Secondary Factor (30%)

Counseling and guidance referrals are a recognized secondary signal for at-risk behavior. Research shows that students referred to guidance services are already exhibiting elevated behavioral risk. A study examining counselor-to-student ratios found that schools with **lower student-to-counselor ratios had significantly fewer disciplinary problems**, confirming the relationship between guidance involvement and behavioral outcomes.

Additionally, ODR-based research established that guidance referrals provide supplementary predictive value beyond disciplinary records alone — they capture behavioral concerns that may not yet have escalated into formal violations.

> *"Counseling referrals provide a supplementary signal for at-risk behavior, particularly for students whose problems have not yet escalated to formal disciplinary action."*

**Supporting studies:**
- [A Descriptive Study of School Discipline Referrals in First Grade — PMC](https://pmc.ncbi.nlm.nih.gov/articles/PMC1828691/)
- [Do Lower Student to Counselor Ratios Reduce School Disciplinary Problems? — ResearchGate](https://www.researchgate.net/publication/4748548_Do_Lower_Student_to_Counselor_Ratios_Reduce_School_Disciplinary_Problems)
- [Examining the Validity of Office Discipline Referrals as an Indicator of Student Behavior Problems](https://www.researchgate.net/publication/230276488_Examining_the_validity_of_office_discipline_referrals_as_an_indicator_of_student_behavior_problems)

---

### Why Violations Are Tiered by Severity (10 / 25 / 40 Points)

Research from PBIS (School-Wide Positive Behavioral Interventions and Supports) explicitly distinguishes between **minor and major ODRs**, treating them as qualitatively different behavioral risk indicators. Studies confirm that major violations are significantly more associated with chronic behavioral problems and future disciplinary incidents than minor ones. A study predicting major vs. minor ODRs found that the two categories carry distinct predictive profiles — major ODRs are more strongly linked to externalizing behaviors and long-term risk.

The point values (Minor=10, Moderate=25, Major=40) are operationalized from this research direction — each tier is weighted to reflect the proportionally greater risk associated with more severe violations.

> *"Major violations are weighted proportionally higher, consistent with PBIS research distinguishing minor and major ODRs as qualitatively different behavioral risk indicators."*

**Supporting studies:**
- [Predicting Major vs. Minor Office Discipline Referrals Using a Behavior Screener — Georgia State University](https://scholarworks.gsu.edu/iph_theses/816/)
- [Patterns of Minor Office Discipline Referrals in Schools using SWIS — Center on PBIS](https://www.pbis.org/resource/patterns-of-minor-office-discipline-referrals-in-schools-using-swis)
- [ODRs Minor v. Major Behaviors — CUSD](https://www.cusdk12.org/documents/ODRs-Minor-v.-Major-Behaviors-1.pdf)

---

## Where It Appears in the System

| Location | Description |
|----------|-------------|
| Discipline Module → Risk Assessment Tab | Full student table with score bars, risk level badges, search/filter, compute buttons |
| Admin Dashboard → Predictive Analytics Section | 4 summary cards, doughnut chart (risk level distribution), bar chart (high-risk by course), top 5 at-risk students |

---

## How Scores Are Triggered

Scores are computed on-demand:
- **Compute All Scores** — recalculates risk scores for all students
- **Recompute (per student)** — recalculates for a single student

Each student has one row in the `risk_prediction` table storing their latest `risk_score`, `risk_level`, `factors` (JSON breakdown), and `prediction_date`.

---

## Limitations and Ethical Notes

- The system flags students for **early intervention**, not punishment. Risk scores should be treated as a tool for proactive support.
- A student with a high score is not guaranteed to commit a future violation — the score reflects statistical likelihood based on patterns, not deterministic prediction.
- The specific point values and weights are operationalized from research direction. As the system collects more data over time, these values can be recalibrated.
- Framed as a **"Risk Scoring System"** rather than a "Prediction Model" — more defensible, more accurate, and more ethically appropriate.

---

## Key Source Files

| File | Purpose |
|------|---------|
| `app/Services/RiskScoringService.php` | Core scoring logic |
| `app/Http/Controllers/Admin/DisciplineController.php` | `computeRiskAll()`, `computeRiskOne()` |
| `app/Http/Controllers/AdminDashboardController.php` | Dashboard risk summary data |
| `resources/js/Pages/Admin/Discipline/Index.vue` | Risk Assessment tab UI |
| `resources/js/Pages/Admin/Dashboard.vue` | Predictive Analytics dashboard section |
