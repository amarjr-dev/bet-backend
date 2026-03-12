# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtenha seu token via <code>POST /api/auth/login</code> e inclua-o como <code>Authorization: Bearer {token}</code>.
