# htdocs/margin - Margin Analysis Module

Margin calculation and analysis for commercial documents.

## Module Info

| Property | Value |
|----------|-------|
| Module | margin |
| Permission Key | margins |

## Directory Structure

```
margin/
├── index.php             # Margin dashboard
├── productMargins.php    # Margins by product
├── customerMargins.php   # Margins by customer
├── agentMargins.php      # Margins by sales rep
└── tabs/                 # Margin tabs for documents
```

## Margin Calculation

Margin is calculated as: `Selling Price - Cost Price`

### Cost Price Sources

Configured via `MARGIN_TYPE`:

| Value | Source |
|-------|--------|
| costprice | Product cost price field |
| pmp | Weighted average price (PMP) |
| pa | Buying price from supplier |

## Integration

Margin data appears on:

- Proposals (propal)
- Customer orders (commande)
- Customer invoices (facture)

## Permissions

- `$user->hasRight('margins', 'liretous')` - View all margins
- `$user->hasRight('margins', 'creer')` - Access margin reports
