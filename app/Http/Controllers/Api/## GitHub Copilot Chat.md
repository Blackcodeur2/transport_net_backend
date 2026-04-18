## GitHub Copilot Chat

- Extension: 0.44.0 (prod)
- VS Code: 1.116.0 (560a9dba96f961efea7b1612916f89e5d5d4d679)
- OS: win32 10.0.22000 x64
- GitHub Account: blackcodeur237

## Network

User Settings:
```json
  "http.systemCertificatesNode": true,
  "github.copilot.advanced.debug.useElectronFetcher": true,
  "github.copilot.advanced.debug.useNodeFetcher": false,
  "github.copilot.advanced.debug.useNodeFetchFetcher": true
```

Connecting to https://api.github.com:
- DNS ipv4 Lookup: 140.82.121.5 (6065 ms)
- DNS ipv6 Lookup: Error (3635 ms): getaddrinfo ENOTFOUND api.github.com
- Proxy URL: None (11 ms)
- Electron fetch (configured): timed out after 10 seconds
- Node.js https: timed out after 10 seconds
- Node.js fetch: timed out after 10 seconds

Connecting to https://api.individual.githubcopilot.com/_ping:
- DNS ipv4 Lookup: timed out after 10 seconds
- DNS ipv6 Lookup: Error (6875 ms): getaddrinfo ENOTFOUND api.individual.githubcopilot.com
- Proxy URL: None (4 ms)
- Electron fetch (configured): HTTP 200 (8892 ms)
- Node.js https: timed out after 10 seconds
- Node.js fetch: timed out after 10 seconds

Connecting to https://proxy.individual.githubcopilot.com/_ping:
- DNS ipv4 Lookup: 20.199.39.224 (2193 ms)
- DNS ipv6 Lookup: Error (3237 ms): getaddrinfo ENOTFOUND proxy.individual.githubcopilot.com
- Proxy URL: None (282 ms)
- Electron fetch (configured): timed out after 10 seconds
- Node.js https: timed out after 10 seconds
- Node.js fetch: timed out after 10 seconds

Connecting to https://mobile.events.data.microsoft.com: timed out after 10 seconds
Connecting to https://dc.services.visualstudio.com: timed out after 10 seconds
Connecting to https://copilot-telemetry.githubusercontent.com/_ping: timed out after 10 seconds
Connecting to https://telemetry.individual.githubcopilot.com/_ping: timed out after 10 seconds
Connecting to https://default.exp-tas.com: timed out after 10 seconds

Number of system certificates: 70

## Documentation

In corporate networks: [Troubleshooting firewall settings for GitHub Copilot](https://docs.github.com/en/copilot/troubleshooting-github-copilot/troubleshooting-firewall-settings-for-github-copilot).