# Operations Center Route Catalogue

The live route catalogue is exposed through:

- `/super-admin/operations-center/journey-flow`
- `/super-admin/operations-center/mind-map`
- `/super-admin/operations-center/technical-map`
- `/super-admin/operations-center/api/graph/data?view=journey_flow`
- `/super-admin/operations-center/api/graph/node/{id}`

Only real GET routes without required parameters receive Open Page links. Parameterised routes, API webhooks, POST/PATCH/DELETE actions, and generated framework internals do not receive fabricated links.

## View Scopes

- Journey Flow: operational lifecycle and user-facing application pages.
- Mind Map: broad product and role map.
- Technical Map: route/source/queue/service traces with progressive node expansion.
