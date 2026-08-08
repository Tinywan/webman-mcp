# Examples

`Calculator/CalculatorServer.php` is a runnable Tool demonstration Server. It deliberately enables
`AllowAnonymousAuthenticator` for local development; production Servers should install an explicit
authenticator instead.

`Library/LibraryServer.php` demonstrates exact Resources, URI Templates, Principal-aware discovery,
and per-read handlers. Both examples enable anonymous access only for local development.

`Assistant/AssistantServer.php` demonstrates Prompt arguments, rendering, and bounded Completion.

`Status/StatusServer.php` demonstrates an authorized, bounded `subscriptions/listen` provider with
Tool-list and Resource-update notifications. All examples require explicit production authentication
and authorization before deployment.
