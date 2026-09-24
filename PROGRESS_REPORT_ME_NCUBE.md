# Sprint 7-8 Progress Report — ME Ncube (22152725)

**Report Type:** Individual Progress Report
**Reporting Member:** ME Ncube (22152725)
**Role:** Project Lead, Backend Lead and Deployment Owner
**Reporting Period:** Sprint 7-8 (Beyond MVP)
**Submission Target:** Thursday, 17 September 2026

---

## 1. My Progress — Where I Am Right Now

As Project Lead, my workstream covers deployment (website and Firebase), backend coordination, release identity, and final submission assembly.

**Current status:** In progress — deployment planning stage.

At this point, the application has not yet been deployed to a live environment. I am currently evaluating hosting options, with Google Cloud Platform as the preferred candidate, alongside alternative PHP-compatible hosting environments. Backend development across Sprints 1 to 6 is complete. The remaining work is to move that build into a released, accessible, and reproducible state.

**Overall completion estimate for my role:** Approximately 25 percent — planning complete, execution pending.

---

## 2. What I Have Done So Far

| # | Deliverable | Status |
|---|---|---|
| 1 | Backend APIs developed across Sprints 1 to 6 (`auth.php`, `sync.php`, `users.php`, `alerts.php`, `ai-insights.php`, `bill-calculator.php`) | Complete |
| 2 | Live deployment environment identified (Google Cloud Platform under evaluation) | In progress |
| 3 | Firebase project configuration review | Started |
| 4 | `.env` migration plan drafted | Planned |
| 5 | Release identity plan (`Sprint-8-Release` Git tag) prepared | Planned |
| 6 | Daily stand-up structure defined | Complete |

---

## 3. How I Have Done It

### Development approach (Sprints 1 to 6)

- PHP and MySQL for backend logic and data persistence
- Firebase Realtime Database for live meter data streaming
- Firebase Authentication for secure user login and role management
- REST-style APIs under `/api/` for frontend communication

### Deployment planning (Sprint 7-8)

- Evaluating Google Cloud Platform as the primary hosting environment due to its reliability, HTTPS support, and scalability
- Alternative options under consideration: cPanel-based shared hosting or a cloud virtual machine for simpler PHP deployment
- Firebase deployment will be handled from the Firebase Console (Authentication, Realtime Database, Storage, and Security Rules)
- Secrets will be moved into a `.env` file with a matching `.env.example` committed to the repository

### Coordination approach

- Daily stand-ups planned with each member reporting completed work, current work, and blockers
- Report sections assigned to ensure every member owns exactly 2 sections — 14 sections divided by 7 members

---

## 4. What Is Still Left

### My remaining tasks before submission

| # | Task | Deadline |
|---|---|---|
| 1 | Finalise hosting platform (Google Cloud Platform or alternative) | Week 1 |
| 2 | Deploy the full stack (PHP, MySQL, Firebase) to live HTTPS | Week 1 |
| 3 | Move all secrets to `.env`; create `.env.example` | Week 1 |
| 4 | Configure Firebase production settings (Auth, Database, Storage, Rules) | Week 2 |
| 5 | Verify Firebase to PHP to MySQL end-to-end flow on live environment | Week 2 |
| 6 | Create and push `Sprint-8-Release` Git tag | Week 2 |
| 7 | Write `RELEASE_NOTES.md` | Week 2 |
| 8 | Compile final submission pack (15 items per Section 6 of brief) | Week 3 |
| 9 | Write Section 1 (Cover Page) and Section 2 (Executive Release Status) | Week 3 |

**Estimated completion:** End of Week 2 for deployment, end of Week 3 for submission pack.

---

## 5. How My Work Accommodates Sprint 7-8

Sprint 7-8 is a release-readiness sprint, not a feature-development sprint. My role is central to this.

| Sprint 7-8 Requirement | How I Address It |
|---|---|
| Deployed or installable MVP | Deploy website and Firebase to live HTTPS |
| Works outside developer's machine | Hosting on Google Cloud Platform or alternative |
| Production-like configuration, secrets excluded | `.env` migration and `.env.example` |
| Reproducible from README and release package | Release notes and submission pack |
| Stable Git tag matching the demo | `Sprint-8-Release` tag |
| Accessible to marker without private credentials | Public URL and documented test accounts |

Without my deployment workstream, the release cannot be evidenced. This is the critical path for Sprint 7-8.

---

# Group Progress — Where We Are As a Team

## 1. Group Status Right Now

The team has completed the feature-development phase across Sprints 1 to 6. Sprint 7-8 work has now begun, focused on release readiness rather than new features.

**Overall group release-readiness estimate:** Approximately 30 percent — the application is built, but deployment, testing evidence, and documentation are still in progress.

---

## 2. What the Group Has Done

| Area | Status |
|---|---|
| 8 core features built (dashboard, history, alerts, billing, chatbot, admin, thresholds, properties) | Complete |
| Backend APIs implemented | Complete |
| Firebase integration | Complete |
| UI and UX designed (dark and light modes, responsive) | Complete |
| MySQL database schema designed | Complete |
| All 14 report sections assigned (2 per member) | Complete |

---

## 3. How the Group Has Done It

- **Tech stack:** PHP, MySQL, Firebase, JavaScript, Chart.js
- **Workflow:** Git-based with clear commit messages and daily stand-ups
- **Division of labour:** Every member owns exactly 2 report sections, 2 major deliverables, 2 supporting tasks, and produces 4 evidence files
- **Sprint focus:** Turn the built MVP into a released, tested, secure, and supportable release

---

## 4. What Is Still Left — Group-Wide

| Member | Outstanding Work |
|---|---|
| ME Ncube | Deploy website and Firebase; `.env`; Git tag; Sections 1, 2 |
| S Sibeko | Database backup and restore; architecture diagram; Sections 4, 6 |
| KM Selebano | Design system document; reflection compiler; Sections 3, 14 |
| NM Yingwani | UAT with 3 users; limitations document; Sections 9, 11 |
| ZE Mbangi | Test suite; Bug Register; rollback plan; Sections 8, 10 |
| B Sithole | Firebase listener audit; environment documentation; Sections 5, 12 |
| HN Bila | Security audit; POPIA; contribution table; Sections 7, 13 |

---

## 5. Sprint 7-8 Alignment (Group Level)

The group understands Sprint 7-8 as a release-readiness sprint. Every member's task list directly maps to a Sprint 7-8 requirement:

- **Deployment** — ME Ncube
- **Integration evidence** — S Sibeko and B Sithole
- **Testing evidence** — ZE Mbangi
- **Security and POPIA** — HN Bila
- **User acceptance testing** — NM Yingwani
- **Design and documentation** — KM Selebano
- **Individual accountability** — HN Bila (Section 13)

---

## 6. Immediate Group Actions

1. **ME Ncube** — Finalise hosting (Google Cloud Platform or alternative) and begin deployment
2. **S Sibeko** — Export schema and produce first database backup
3. **KM Selebano** — Begin design system documentation
4. **NM Yingwani** — Identify and recruit 3 UAT users
5. **ZE Mbangi** — Open the Bug Register
6. **B Sithole** — Start Firebase listener audit
7. **HN Bila** — Run secret scan on repository

---

## Summary — My Section in One View

| Item | Status |
|---|---|
| Backend APIs | Complete |
| Website deployment | In progress — Google Cloud Platform under evaluation |
| Firebase deployment | Planned |
| `.env` migration | Planned |
| Git tag `Sprint-8-Release` | Planned |
| Release notes | Planned |
| Submission pack | Not started |
| Report Sections 1 and 2 | Draft pending |

**My honest position:** The code is ready. The deployment is the next step. I have identified Google Cloud Platform as the preferred hosting route and am preparing to execute the migration, Firebase configuration, and release evidence this sprint.

---

## One-Line Summary

**As Project Lead, my Sprint 7-8 work — website and Firebase deployment, release identity, and final submission — is currently in the planning stage. The group has completed feature development; the sprint now focuses on turning the built MVP into a live, tested, documented, and supportable release.**
