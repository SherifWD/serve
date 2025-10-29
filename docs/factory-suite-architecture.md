# Factory Suite Transformation Blueprint

## 1. Vision & Scope
- Convert the current restaurant POS into a configurable factory enterprise suite that supports all 20 functional domains (ERP, MES, PLM, SCM, etc.).
- Deliver a single backbone where factories (tenants) subscribe to domain-specific modules, exposing both web and mobile APIs.
- Prioritise clean manufacturing terminology, extensible architecture, and domain isolation for gradual rollout.

## 2. Guiding Principles
- **Modular Domains:** Group functionality into bounded contexts under `App\Domain\{Module}` with clear aggregates, use cases, and HTTP/API surfaces.
- **Multi-Tenant & Subscription First:** Every resource links to a `Tenant` (factory). Module access derives from subscription plans and per-tenant overrides.
- **API-First Delivery:** RESTful (and future GraphQL/websocket) APIs backed by service classes; web/mobile clients consume the same contracts.
- **Data Governance:** Rename legacy POS tables/fields to factory terminology; enforce naming via new migrations/adapters.
- **Event-Driven Backbone:** Publish domain events for cross-module workflows (e.g., MES production events feeding BI dashboards).
- **Security & Compliance:** Role-based access paired with module-level policies; audit log for every critical action.

## 3. Core Platform Changes (Phase 0)
1. **Foundational Models**
   - `Tenant` (factories), `TenantUser`, `TenantModule`, `SubscriptionPlan`, `SubscriptionAddon`.
   - `Module` registry enumerating ERP/MES/... with version, required features, route prefixes.
2. **Access Control**
   - Replace ad-hoc `role` strings with RBAC: `Role`, `Permission`, `RolePermission`, `UserRole`, plus contextual policies per module.
   - Middleware to enforce tenant + module access (`HasModuleAccess`).
3. **Naming & Schema Cleanup**
   - Introduce migration batch that renames POS-specific tables:
     - `restaurants` → `tenants`; `branches` → `sites`; `menus/products` → `catalog_items`; etc.
   - Provide view models to keep legacy endpoints working during transition.
4. **Service Layer & DTOs**
   - Create `App\Application` layer with use cases, mappers, DTO responses; controllers become thin.
5. **Shared Infrastructure**
   - Event bus (Laravel events), job queues, notification channels, file storage (for DMS, PLM).
   - Observability: structured logs per tenant, metrics pipeline hooks.

## 4. Module Layout
```
app/
  Domain/
    ERP/
      Models/
      Actions/
      Http/
      Policies/
      Transformers/
    MES/
    PLM/
    ...
  Platform/
    Tenancy/
    Identity/
    Billing/
    Audit/
```
Each domain ships with:
- Entities + repositories
- Application actions/services
- HTTP controllers (API + web views) registered conditionally by module
- Mobile-specific endpoints under `/api/mobile/{module}`

## 5. Domain MVPs & Key Entities
1. **ERP Backbone**
   - Master data: `Site`, `Department`, `CostCenter`, `ItemMaster`, `BOM`.
   - Workflows: approvals, cross-module config.
2. **MES**
   - `WorkOrder`, `ProductionLine`, `Machine`, `ShiftSchedule`, `ProductionEvent`.
   - Real-time endpoints (Pusher/Reverb) for status tablets.
3. **PLM**
   - `ProductDesign`, `EngineeringChangeRequest`, `Revision`, `Document`.
   - CAD/document storage via DMS.
4. **SCM**
   - `Supplier`, `PurchaseOrder`, `InboundShipment`, `ASN`, `DemandPlan`.
5. **WMS**
   - `Warehouse`, `StorageBin`, `InventoryLot`, `TransferOrder`, `CycleCount`.
6. **QMS**
   - `InspectionPlan`, `InspectionResult`, `NonConformity`, `CAPA`, `Audit`.
7. **HRMS**
   - `Worker`, `EmploymentContract`, `AttendanceRecord`, `PayrollRun`, `LeaveRequest`, `TrainingSession`.
8. **CMMS/EAM**
   - `Asset`, `MaintenancePlan`, `WorkOrder`, `SparePart`, `Technician`.
9. **Finance & Accounting**
   - `LedgerAccount`, `JournalEntry`, `ARInvoice`, `APPayment`, `CostAllocation`.
10. **CRM**
    - `Account`, `Contact`, `Opportunity`, `ServiceTicket`, `Warranty`.
11. **BI / Analytics**
    - Unified data mart schema, KPI definitions, dashboard metadata, scheduled reports.
12. **HSE**
    - `Incident`, `SafetyObservation`, `TrainingRecord`, `ComplianceChecklist`.
13. **DMS**
    - `Document`, `DocumentVersion`, `Folder`, `AccessPolicy`.
14. **Visitor & Access Control**
    - `Visitor`, `VisitRequest`, `Badge`, `AccessLog`, kiosk integration.
15. **IoT / SCADA**
    - `Device`, `Sensor`, `TelemetryPoint`, `DataStream`, integration connectors.
16. **Procurement & Vendor Portal**
    - Supplier self-service, tender workflow, contract lifecycle.
17. **E-Commerce / Order Portal**
    - B2B catalogs, customer orders feeding ERP/SCM, customer portal.
18. **Budgeting & Cost Control**
    - `Budget`, `BudgetLine`, variance reports, alerts.
19. **Project Mgmt & Engineering Change**
    - `Project`, `Task`, `StageGate`, `ChangeApproval`.
20. **Internal Communication & Workflow**
    - Announcement feeds, request workflows, chat integrations, notification hub.

## 6. Subscription & Pricing
- `SubscriptionPlan` defines included modules, seat counts, API limits.
- `TenantModule` tracks module status (trial, active, suspended) and overrides.
- Discount engine: `DiscountRule` (by tenant, industry, contract length) applied on invoice generation.
- Settings UI: allow ops team to toggle modules per tenant, adjust quotas, upload contracts.

## 7. Migration Strategy
1. **Phase 0 (Infrastructure)**
   - Introduce tenancy tables, RBAC, module registry, feature flags.
   - Create adapters so current POS routes continue but read from renamed tables.
2. **Phase 1 (ERP Core)**
   - Rename core entities (branch → site, menu/product → item, etc.).
   - Stabilise inventory, procurement, finance foundations.
3. **Phase 2+ (Domain Rollout)**
   - Deliver modules in groups (MES+WMS, HRMS+Payroll, QMS+HSE, etc.).
   - For each: migrations, models, controllers, Vue SPA sections, mobile API layer.
4. **Phase N (Sunset POS-specific Artifacts)**
   - Remove restaurant-only concepts once parity achieved.

## 8. Immediate Next Steps
1. Create tenancy & module scaffolding migrations + Eloquent models.
2. Implement middleware and helpers for tenant scoping and module checks.
3. Draft ERP master data module (sites, departments, item master) with clean naming.
4. Refactor existing inventory/order flows to live under ERP/SCM contexts.
5. Build subscription management UI endpoints + Vue pages for module provisioning.

## 9. Tooling & Delivery Notes
- Update seeder/factory strategy to generate realistic factory data per module.
- Use Laravel Actions or Livewire (if desired) for admin UI; keep domain logic in services.
- Ensure OpenAPI (via Scribe) captures new APIs for web/mobile clients.
- Set up automated testing per module (feature tests for API workflows, unit tests for domain services).

## 10. Phase 0 Implementation Snapshot
- Added tenancy and subscription scaffolding (`tenants`, `modules`, `subscription_plans`, tenant-module pivot tables) with corresponding Eloquent models under `app/Platform`.
- Introduced RBAC enhancements (tenant-scoped roles, permissions, and pivots) plus tenant-aware middleware alias `tenant.access`.
- Seeded all 20 modules with an initial subscription plan, default tenant, and sample discount for demo environments.
- API routes now require tenant context alongside Sanctum auth; middleware falls back to the user’s primary tenant when no header is provided.
- `TenantContext` service is bootstrapped via `TenancyServiceProvider`, exposing helpers for future domain scoping.

## 11. Phase 1 (ERP · MES · PLM) Progress
- Delivered tenant-scoped ERP master data models (sites, departments, cost centres, item catalog, BOMs) with fully validated REST controllers/resources (`/api/erp/*`) and Vue admin workflows (`pos-dashboard/src/pages/ERP.vue`).
- Stood up MES execution primitives (production lines, work orders, production events) powering shop-floor dashboards and event logging endpoints (`/api/mes/*`).
- Launched PLM pipelines (product designs, engineering changes, design documents) aligned with ERP items and user audit fields, surfaced via `/api/plm/*` and a dedicated Vue console.
- Updated database seeders to provision exemplar factory data spanning ERP, MES, and PLM domains for instant demo environments (`database/seeders/DatabaseSeeder.php`).
- Documented front/back contracts ready for downstream Flutter clients; REST validation ensures consistent payload expectations for mobile integrations.

## 12. Phase 1 Expansion (SCM · WMS · QMS · HRMS)
- Implemented SCM operations for suppliers, purchase orders, inbound shipments, and demand planning (`/api/scm/*`) with Vue management console at `pos-dashboard/src/pages/SCM.vue`.
- Delivered WMS layers covering warehouses, storage bins, inventory lots, and transfer orders (`/api/wms/*`) plus a handheld-friendly web UI (`pos-dashboard/src/pages/WMS.vue`).
- Rolled out QMS workflows for inspection plans, inspections, non-conformities, CAPA, and audit scheduling (`/api/qms/*`) surfaced in `pos-dashboard/src/pages/QMS.vue`.
- Extended HRMS services for workers, attendance, payroll, leave, and training assignments (`/api/hrms/*`) with corresponding admin tools (`pos-dashboard/src/pages/HRMS.vue`).
- Seeded cohesive demo data spanning the four domains to exercise procurement, logistics, quality, and people processes (`database/seeders/DatabaseSeeder.php`).

## 13. Phase 1 Extension (CMMS - Finance - CRM - BI - HSE)
- Added CMMS/EAM asset registers, preventive plans, work orders, and maintenance logging with REST endpoints under `/api/cmms/*` and a maintenance cockpit at `pos-dashboard/src/pages/CMMS.vue`.
- Hardened Finance & Accounting with tenant-scoped ledger accounts, balanced journal postings, AR/AP management, and a finance console at `pos-dashboard/src/pages/Finance.vue` (validation captured in `app/Domain/Finance/Http/Requests`).
- Delivered CRM account, contact, lead, opportunity, and service case APIs (`/api/crm/*`) alongside the growth workspace UI (`pos-dashboard/src/pages/CRM.vue`).
- Introduced BI KPI catalogues, dashboard composition, widget registry, and KPI snapshot APIs (`/api/bi/*`) paired with analytics tooling (`pos-dashboard/src/pages/BI.vue`).
- Brought HSE incident, audit, action tracking, and training records online (`/api/hse/*`) with a compliance hub at `pos-dashboard/src/pages/HSE.vue` and expanded seeding for maintenance/finance/CRM/BI/HSE datasets (`database/seeders/DatabaseSeeder.php`).

## 14. Phase 1 Expansion (DMS - Visitor - IoT - Procurement - Commerce)
- Document Management System delivers folder hierarchies, document metadata, and version control under `/api/dms/*` with a Vue filing workspace (`pos-dashboard/src/pages/DMS.vue`) and seeded ISO procedure content.
- Visitor & Access Control enables watchlist-ready visitor registries and gate entries via `/api/visitor/*`, surfaced through the visitor console (`pos-dashboard/src/pages/Visitor.vue`) for kiosk/mobile parity.
- IoT/SCADA integration now tracks devices, sensors, and telemetry snapshots (`/api/iot/*`) and streams real-time data into the IoT board (`pos-dashboard/src/pages/IoT.vue`).
- Procurement & Vendor Portal covers supplier onboarding, tendering, response capture, and purchase requests (`/api/procurement/*`) with a sourcing cockpit (`pos-dashboard/src/pages/Procurement.vue`) and representative seed data.
- Commerce / Order Portal powers B2B customer management and order capture (`/api/commerce/*`) alongside a lightweight storefront admin (`pos-dashboard/src/pages/Commerce.vue`), including seeded enterprise orders tied to ERP inventory.

---
This blueprint governs the initial transformation. As modules are developed, extend this document with detailed sequence diagrams, data contracts, and cross-module integration notes.
