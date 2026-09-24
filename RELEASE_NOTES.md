# Sprint-8-Release — Smart Water Guardian

**Release Date:** 17 September 2026
**Version:** 1.0.0
**Git Tag:** `Sprint-8-Release`
**Repository:** [insert GitHub URL]
**Deployed URL:** [insert live HTTPS URL once deployed]

---

## Release Summary

This release delivers a fully integrated, deployable Smart Water Guardian MVP. It transitions the project from a developer-machine build into a live, hosted, and reproducible release as required by Sprint 7-8.

---

## Delivered Scope

The following features are included in this release:

1. User registration with Firebase Authentication and MySQL synchronization
2. Role-based login for consumers and administrators
3. Live water usage dashboard (flow rate, total volume, battery, hourly saved, peak flow)
4. Usage history with daily, hourly, pressure, and leak analysis views
5. Alerts and admin-to-user messaging system
6. Billing module with tiered tariff calculation and PDF invoice generation
7. AI chatbot (AquaBot) with live meter data integration
8. Admin panel (users, devices, properties, billing, alerts, reviews)
9. Thresholds management for automated alerting
10. Properties management for consumers

---

## Deployment Details

**Platform:** Google Cloud Platform (primary) or approved alternative
**Runtime:** PHP 8.x
**Database:** MySQL
**Realtime Layer:** Firebase Realtime Database
**Authentication:** Firebase Authentication
**Storage:** Firebase Storage
**HTTPS:** Enabled on live deployment

**Configuration approach:**

- All secrets moved to `.env` (never committed to source control)
- `.env.example` provided with variable names only
- No hard-coded localhost addresses or developer-machine paths in the release

---

## Fixes Since Sprint 7

| Bug ID | Description | Severity | Fix Commit |
|---|---|---|---|
| [BUG-001] | [description] | [severity] | [commit link] |
| [BUG-002] | [description] | [severity] | [commit link] |

*Note: This table is populated from the Bug Register maintained by ZE Mbangi.*

---

## Known Limitations

The following constraints are documented for transparency:

1. **Payment gateways** operate in sandbox mode only (PayFast, Yoco, Ozow, PayPal, Visa, Mastercard integrations are stubs pending production credentials).
2. **AI insights** require at least 3 days of historical usage data to generate meaningful forecasts.
3. **ESP32 hardware verification** is pending a final field test against the deployed backend.
4. **Firebase free-tier quotas** apply — high-volume usage may require a paid plan.
5. **Demo billing tiers** (R4 / R10 / R20 per kL) are used for demonstration purposes and may not reflect live municipal tariffs.

---

## Responsible Members

| Member | Responsibility in This Release |
|---|---|
| ME Ncube | Project Lead, Backend Lead, Deployment (Website and Firebase) |
| S Sibeko | IoT and SQL Database Lead |
| KM Selebano | UI and UX Designer |
| NM Yingwani | UI and UX Designer, User Acceptance Testing |
| ZE Mbangi | QA and Testing Lead |
| B Sithole | Frontend Specialist |
| HN Bila | Security and Analytics Lead |

---

## Release Evidence

The following evidence accompanies this release:

| Evidence | Location |
|---|---|
| Live deployed URL | [insert URL] |
| GitHub repository | [insert link] |
| Git tag | `Sprint-8-Release` |
| Deployment architecture diagram | `docs/architecture.png` |
| Database schema and migrations | `docs/schema.sql`, `docs/migrate.sql` |
| Database backup and restore evidence | `docs/RESTORE_TEST.md` |
| API documentation | `docs/API_DOCUMENTATION.md` |
| Test suite and results | `docs/TEST_RESULTS.md` |
| Bug Register | `docs/BUG_REGISTER.md` |
| Rollback procedure | `docs/ROLLBACK.md` |
| User acceptance testing evidence | `docs/UAT_FEEDBACK.md` |
| Individual contribution table | `docs/CONTRIBUTIONS.md` |

---

## Rollback Plan

If the release fails in production:

1. Redeploy the previous stable tag (if one exists) or revert to the last verified commit.
2. Restore the MySQL database from the most recent backup (`backup_YYYYMMDD.sql`).
3. Restore Firebase data from the export (if applicable).
4. Notify the team and document the incident in the Bug Register.

**Responsible member:** ME Ncube
**Approximate recovery time:** 1 to 2 hours (depending on database size)

---

## Support and Contact

For issues related to this release, contact:

- **Primary:** ME Ncube (22152725) — Project Lead
- **Secondary:** HN Bila (220080694) — Security and Analytics

---

## Next Steps After This Release

- Replace sandbox payment gateway credentials with production credentials.
- Conduct ESP32 field test against the deployed backend.
- Upgrade Firebase plan if usage exceeds free-tier limits.
- Continue collecting user feedback for post-MVP improvements.

---

**End of Release Notes**
