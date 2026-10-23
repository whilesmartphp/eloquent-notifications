## [1.0.0] - 2026-10-23
- Polymorphic notifications for any recipient, scoped per owner through owner-access
- One send stores an in-app notification and delivers it by email and push
- Per-channel, per-type and per-type-per-channel preferences stored through eloquent-model-configuration
- Signed one-click unsubscribe links and List-Unsubscribe headers on every email
- Pluggable push sender and custom channels, with a push sender that sends nothing by default
- API for listing, counting, reading, deleting and preferences
- Configurable model, table, UUID keys, routes, write middleware, controller, resource and response format
- Events after each change, and an import command for moving from a user-keyed notifications table
