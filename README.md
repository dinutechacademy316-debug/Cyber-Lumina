# CyberLumina

<p align="center">
  <img src="cl_logo.jpg" alt="CyberLumina brand logo" width="520">
</p>

<p align="center">
  <strong>Cybersecurity learning • Privacy-aware testing • Security education</strong>
</p>

<p align="center">
  <a href="https://cyberlumina.kesug.com/">Live Website</a> ·
  <a href="https://github.com/dinutechacademy316-debug/Cyber-Lumina">GitHub Repository</a>
</p>

CyberLumina is a web-based cybersecurity project built around a dark, futuristic security interface. The repository includes an account portal, credit and plan pages, an AI-assistant interface, and browser-based demonstration modules related to link handling, camera permissions, location permissions, and network information.

> **Important safety notice:** Some existing routes can interact with sensitive browser or device data. The repository has not been independently audited as a whole. Do not use it to collect photos, location data, or device information from anyone without their informed, explicit consent and appropriate authorization. Do not use deceptive links or attempt to bypass browser permission controls. Complete the security and privacy review described below before any public or production deployment.

## Project links

- **Website:** https://cyberlumina.kesug.com/
- **Repository:** https://github.com/dinutechacademy316-debug/Cyber-Lumina

## Project areas

The current repository includes files associated with the following areas. Their presence in the codebase does not mean that every feature has been security-reviewed or is production-ready.

| Area | Purpose / review focus |
| --- | --- |
| Account and profile pages | Account access, profile settings, and session-related flows |
| Aegis-7 | AI assistant interface and related integration |
| Link demonstrations | Link routing and safe, clearly disclosed testing flows |
| Camera-permission demonstration | Browser camera permissions for a user-initiated, consent-based test |
| Location-permission demonstration | Browser geolocation permissions for a user-initiated, consent-based test |
| Network information | Privacy-aware display of network metadata, with collection minimized |
| Gallery module | User-authorized file selection and upload workflows |
| Credits and plans | Credit balance and plan-related UI / payment integration |
| Notifications and activity | Account activity and notification-related pages |

## Brand assets

- `cl_logo.jpg` — logo asset currently present in the repository.
- `aegis7.png` — Aegis-7 image asset currently present in the repository.

The separate logo image and intro-video files supplied for the latest branding update still need to be uploaded to the repository before they can be referenced as committed assets. Keep source files in a clearly named `assets/` directory and avoid replacing existing assets until the updated site has been checked.

## Technology

The repository currently contains PHP pages, JavaScript, CSS, JSON data, and a Python script. Review the actual configuration and dependencies in your deployment environment before use.

## Local setup

1. Install a supported PHP runtime and a local web server such as Apache.
2. Clone this repository and configure the web server document root to the project directory.
3. Review the database configuration in `db.php`. Use a dedicated local/test database and environment-specific credentials; never commit real passwords, API keys, payment secrets, or production data.
4. Inspect the authentication, payment, file-upload, link-routing, camera, and geolocation code before running it.
5. Start the site only in a private test environment and test with accounts and devices you control.
6. Do not expose the app publicly until the security checklist below has been completed.

> Setup note: the exact database schema, PHP version, and external service requirements should be confirmed from the code and deployment configuration. This README does not assume that a clean installation has been tested.

## Mandatory privacy and security checklist

Before a public or production release:

- [ ] Show a clear explanation of what data each demo requests and why.
- [ ] Require a separate, deliberate user action before requesting camera or location access.
- [ ] Use the browser's native permission prompts; never try to bypass them.
- [ ] Make camera activity visible and provide obvious start, stop, capture, and exit controls.
- [ ] Never capture or transmit images in the background or without the person's informed consent.
- [ ] Never disguise a destination to trick a person into granting a permission or sharing data.
- [ ] Request location only after clear disclosure and explicit consent; collect only the precision actually needed.
- [ ] Minimize IP/device metadata, avoid unnecessary fingerprinting, and do not collect cookies or unrelated identifiers.
- [ ] Define retention periods and provide a practical way to delete collected test data.
- [ ] Secure authentication and administrator routes; verify authorization server-side.
- [ ] Validate uploads and inputs, use prepared database statements, apply CSRF protections where appropriate, and rate-limit sensitive endpoints.
- [ ] Keep API keys and payment secrets outside source control.
- [ ] Use HTTPS, review logs for accidental personal-data exposure, and complete an independent security review.

See [SECURITY.md](SECURITY.md) for reporting security issues and the expected responsible-use boundary.

## Pricing information

The website's displayed plan values supplied for this project are listed below for reference. They should be verified against the live UI and payment configuration before being advertised or charged. Credits and link quotas are not proof that all features work or that the application is production-ready.

| Plan | Displayed price | Displayed quota | Credits |
| --- | ---: | ---: | ---: |
| Starter | $1.70 (LKR 571) | 25 links | 500 |
| Pro | $4.00 (LKR 1,344) | 75 links | 1,500 |
| Elite | $10.00 (LKR 3,361) | 250 links | 5,000 |

**Important:** Camera, geolocation, gallery, and network-data features must only be offered as transparent, consent-based demonstrations with verified safeguards. Do not sell or enable covert capture, deceptive tracking, or non-consensual collection.

## Responsible use

CyberLumina should be used only for education, privacy-aware demonstrations, and authorized testing. Use test data and devices you control, tell participants what will happen, obtain permission before collecting data, and stop the test when consent is withdrawn.

## License

No license file is currently documented here. Unless a license is added by the repository owner, assume that normal copyright restrictions apply and do not presume that reuse, redistribution, or commercial use is permitted.
