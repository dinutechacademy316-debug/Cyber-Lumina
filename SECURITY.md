# Security Policy

## Responsible use

CyberLumina includes code paths related to link handling and sensitive browser/device information. Use the project only in a controlled environment and only with systems, accounts, and participants for which you have permission.

**Non-consensual webcam capture, deceptive permission requests, covert location tracking, and collection of personal/device information without informed permission are outside the acceptable use of this project.** Browser permission controls must not be bypassed.

## Before deployment

Do not deploy publicly until a review has confirmed that:

- Camera and geolocation requests are preceded by clear disclosure and a deliberate user action.
- No photo, location, or device detail is transmitted without the user's knowledge and permission.
- Sensitive data collection is limited to the stated test purpose and to the minimum necessary.
- Sessions can be stopped, and test data can be deleted according to a documented retention policy.
- Authentication, authorization, upload handling, database access, payment callbacks, and administrative endpoints are secured.
- Secrets are configured outside the repository and HTTPS is enabled.

## Reporting a vulnerability

Please do not post credentials, private user data, or weaponizable exploit details in a public issue. Contact the repository owner through a private, appropriate channel and include:

1. A brief description of the affected component.
2. The potential impact.
3. Minimal reproduction steps using a test account or test environment.
4. Any suggested mitigation.

Please allow time for the issue to be reviewed and fixed before public disclosure.
