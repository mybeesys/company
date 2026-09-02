<?php

return [
    'legend' => 'Hover the information icon to see exactly what each permission unlocks on that screen.',
    'unavailable' => 'This action is not used on this screen.',
    'search' => 'Search for a module or screen...',
    'expand_all' => 'Expand all',
    'collapse_all' => 'Collapse all',
    'selected_count' => ':count selected',
    'screens_count' => ':count screens',
    'no_results' => 'No permissions match this search.',
    'all_screens' => 'All screens',
    'modules_nav' => 'Modules',
    'screen' => 'Screen',
    'select_all' => 'Select all',
    'role_status' => 'Role status',
    'role_active' => 'Active',
    'role_inactive' => 'Inactive',
    'role_page_lead' => 'Name the role, then grant dashboard permissions by module and screen.',
    'module_fallback' => 'Dashboard permissions for the «:module» module.',
    'entity_fallback' => 'The «:entity» screen in the dashboard.',
    'module_all_title' => 'All screens',
    'module_all_body' => 'Shortcut row that applies the same action to every screen in «:module».',
    'all_row_title' => ':action — all «:module» screens',
    'all_row_body' => 'Grants «:action» on every screen in «:module». Use this only when the role needs the whole module; otherwise tick screens one by one.',

    'action_labels' => [
        'show' => 'View',
        'print' => 'Print',
        'create' => 'Create',
        'update' => 'Edit',
        'delete' => 'Delete',
    ],

    'columns' => [
        'show' => 'Opens the screen and reads data. Does not save, delete, or print.',
        'print' => 'Allows printing or PDF/Excel export. Does not change data.',
        'create' => 'Allows adding a new record. Not enough to edit or delete existing ones.',
        'update' => 'Allows saving changes to existing records, including form save buttons.',
        'delete' => 'Allows deleting records. Sensitive — grant only when the role truly needs it.',
    ],

    'action_bodies' => [
        'show' => 'View opens «:entity» read-only: lists and details without saving or deleting.',
        'print' => 'Print exports or prints «:entity» documents without changing data.',
        'create' => 'Create adds a new «:entity» record. Edit and delete are separate permissions.',
        'update' => 'Edit saves changes to existing «:entity» records (save / update actions).',
        'delete' => 'Delete removes «:entity» records. Hard to undo — grant carefully.',
    ],

    'modules' => [
        'setting' => [
            'title' => 'Settings',
            'body' => 'Company and system settings: the general page, branches, tables, and QR. Each general-settings tab has its own row so you can grant view without save.',
        ],
        'establishments' => [
            'title' => 'Establishments',
            'body' => 'Branches and company profile. POS/kitchen devices follow branch update because they have no separate row.',
        ],
        'employees' => [
            'title' => 'Employees',
            'body' => 'Employee files, dashboard roles, POS roles, and scheduling.',
        ],
        'sales' => [
            'title' => 'Sales',
            'body' => 'Sales invoices, quotations, returns, customers, and receipts.',
        ],
        'purchases' => [
            'title' => 'Purchases',
            'body' => 'Purchase invoices, purchase orders, suppliers, and returns.',
        ],
        'products' => [
            'title' => 'Products',
            'body' => 'Items, categories, modifiers, prices, and menus.',
        ],
        'inventory' => [
            'title' => 'Inventory',
            'body' => 'Stock quantities, transfers, prep, waste, and the inventory dashboard.',
        ],
        'accounting' => [
            'title' => 'Accounting',
            'body' => 'Chart of accounts, journals, vouchers, and financial reports.',
        ],
        'screens' => [
            'title' => 'Screens',
            'body' => 'Ad materials, playlists, and display devices on the screens page.',
        ],
        'zatca' => [
            'title' => 'Tax connection',
            'body' => 'ZATCA e-invoicing settings and operations.',
        ],
        'reports_module' => [
            'title' => 'General reports',
            'body' => 'Each report on the general reports page has its own view and print row. The all-row covers every report.',
        ],
        'screen_module' => [
            'title' => 'Screens',
            'body' => 'Full access to the screens page.',
        ],
        'dashboard' => [
            'title' => 'Dashboard',
            'body' => 'The home item at the top of the sidebar. Sales, purchases, products, inventory, and accounting dashboards stay in their own modules.',
        ],
        'Franchise Companies' => [
            'title' => 'Franchise',
            'body' => 'Franchise companies, branches, products, and menus. The all-row covers every tab.',
        ],
        'my_companies' => [
            'title' => 'My companies',
            'body' => 'Sidebar «My companies»: companies linked to the same email on /my-companies.',
        ],
        'referrals' => [
            'title' => 'Share and earn',
            'body' => 'Sidebar «Share and earn»: invite link and stats on /referrals.',
        ],
    ],

    'entities' => [
        'setting.all' => [
            'title' => 'All settings',
            'body' => 'Shortcut for every Settings screen. Covers tabs, tables, and QR. Company profile stays under Establishments.',
        ],
        'setting.General setting' => [
            'title' => 'General settings (currency)',
            'body' => 'Only the currency / basic settings tab. Does not open taxes, mail, or invoices unless those rows are granted.',
        ],
        'setting.notifications' => [
            'title' => 'Notification templates',
            'body' => 'Internal, email, and SMS templates for sales, employees, and inventory events.',
        ],
        'setting.mail' => [
            'title' => 'Email settings',
            'body' => 'SMTP server used to send system email.',
        ],
        'setting.sms' => [
            'title' => 'SMS settings',
            'body' => 'SMS provider configuration.',
        ],
        'setting.prefix' => [
            'title' => 'Prefix settings',
            'body' => 'Number prefixes for invoices and vouchers.',
        ],
        'setting.invoice' => [
            'title' => 'Invoice settings',
            'body' => 'Invoice layout, coupons, and sell-with-modifiers. The sales/purchases tabs on this page follow this row.',
        ],
        'setting.inventory costing' => [
            'title' => 'Inventory costing',
            'body' => 'Costing method and rebuild. Changing this affects cost figures — grant to finance/admin only.',
        ],
        'setting.taxes' => [
            'title' => 'Taxes',
            'body' => 'Tax rates used on invoices. View the list, create a rate, edit, or delete a non-default rate.',
        ],
        'setting.inventory policy' => [
            'title' => 'Inventory policy',
            'body' => 'System-wide inventory tracking policy (e.g. perpetual).',
        ],
        'setting.modules' => [
            'title' => 'Module management',
            'body' => 'Enable or disable product modules for the company.',
        ],
        'setting.default unit' => [
            'title' => 'Default unit',
            'body' => 'Default unit of measure when creating items.',
        ],
        'setting.reward points' => [
            'title' => 'Loyalty points',
            'body' => 'Rules for earning and redeeming customer loyalty points.',
        ],
        'setting.tables' => [
            'title' => 'Tables',
            'body' => 'Floor tables tree: add, edit, and delete tables.',
        ],
        'setting.tables_qr' => [
            'title' => 'Table QR',
            'body' => 'QR codes for table self-ordering. Print saves the code image.',
        ],
        'setting.menu_qr' => [
            'title' => 'Menu QR',
            'body' => 'Guest menu links, tokens, and menu schedules. Does not include menu ratings.',
        ],
        'setting.menu_feedback' => [
            'title' => 'Menu ratings',
            'body' => 'Guest ratings from the public menu. View-only; guests can still submit without this permission.',
        ],
        'establishments.all' => [
            'title' => 'All establishments',
            'body' => 'Shortcut for every branch and company permission in this module.',
        ],
        'establishments.establishments' => [
            'title' => 'Branches',
            'body' => 'Sidebar «Branches»: view the branches table and tree without creating or editing a branch.',
        ],
        'establishments.establishment' => [
            'title' => 'Branch',
            'body' => 'Create, edit, or delete a branch, including cashier settings on the branch. Devices follow update.',
        ],
        'establishments.company' => [
            'title' => 'Company',
            'body' => 'Legal company profile on the Company details tab (name, VAT, logo).',
        ],
        'screens.main' => [
            'title' => 'Home page',
            'body' => 'Enter the screens page. Tabs can still be limited by the ad, playlist, and device rows.',
        ],
        'screens.Ad materials' => [
            'title' => 'Ad materials',
            'body' => 'Advertisement content shown on screens.',
        ],
        'screens.Playlists' => [
            'title' => 'Playlists',
            'body' => 'Order and schedule materials on screens.',
        ],
        'screens.Devices' => [
            'title' => 'Screen devices',
            'body' => 'Display devices linked to the venue from the screens page.',
        ],
        'zatca.Settings' => [
            'title' => 'ZATCA settings',
            'body' => 'E-invoicing onboarding and connection data.',
        ],
        'zatca.Operations' => [
            'title' => 'ZATCA operations',
            'body' => 'Submit and follow electronic invoices.',
        ],
        'my_companies.My companies' => [
            'title' => 'My companies',
            'body' => 'The /my-companies page: companies linked to the same email. Switching from the navbar stays available.',
        ],
        'referrals.Referrals' => [
            'title' => 'Share and earn',
            'body' => 'The /referrals page: invite link and stats. Create sends email invites.',
        ],
        'dashboard.Dashboard' => [
            'title' => 'Dashboard',
            'body' => 'The overview tab on /dashboard. Module dashboards are separate rows in their modules.',
        ],
        'Franchise Companies.all' => [
            'title' => 'All franchise',
            'body' => 'Shortcut for every franchise tab: companies, branches, products, and menus.',
        ],
        'Franchise Companies.Companies' => [
            'title' => 'Franchise companies',
            'body' => 'Franchise company list and contracts on the company page.',
        ],
        'Franchise Companies.Branches' => [
            'title' => 'Franchise branches',
            'body' => 'Granted-company branches from the franchise page.',
        ],
        'Franchise Companies.Products' => [
            'title' => 'Franchise products',
            'body' => 'Grant parent products to a franchisee and approve requests. Does not create catalog items.',
        ],
        'Franchise Companies.Menus' => [
            'title' => 'Custom menu grants',
            'body' => 'Grant custom menus to a franchisee.',
        ],
        'reports_module.all' => [
            'title' => 'All general reports',
            'body' => 'Shortcut for every report on the general reports page. View opens every card; print exports or prints every report.',
        ],
        'reports_module.Sell payment report' => [
            'title' => 'Sales payment report',
            'body' => 'Sales payments from the general reports page. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Product sales report' => [
            'title' => 'Product sales report',
            'body' => 'Item sales from the general reports page. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Sales comparison report' => [
            'title' => 'Sales comparison report',
            'body' => 'Compare sales periods. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Weekday sales report' => [
            'title' => 'Weekday sales report',
            'body' => 'Sales by day of week. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Purchase payment report' => [
            'title' => 'Purchase payment report',
            'body' => 'Purchase payments. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Product purchase report' => [
            'title' => 'Product purchase report',
            'body' => 'Item purchases. View is read-only; print is Excel/PDF.',
        ],
        'reports_module.Product inventory report' => [
            'title' => 'Inventory operations report',
            'body' => 'Stock movements from the general reports page. View is read-only; print exports.',
        ],
        'reports_module.Product inventory summary' => [
            'title' => 'Inventory balance report',
            'body' => 'Stock balances. View is read-only; print exports.',
        ],
        'reports_module.Product stock report' => [
            'title' => 'Product stock report',
            'body' => 'Per-item stock. View is read-only; print exports.',
        ],
        'reports_module.Profit Loss' => [
            'title' => 'Profit and loss',
            'body' => 'Operational profit and loss. View is read-only; print prints the page.',
        ],
        'reports_module.Purchase sell' => [
            'title' => 'Purchases and sales',
            'body' => 'Purchases vs sales summary. View is read-only; print prints the page.',
        ],
        'reports_module.Register report' => [
            'title' => 'Cash register report',
            'body' => 'Cashier register. View is the list and details; print opens the print page.',
        ],
    ],

    'exact' => [
        'setting.taxes.show' => 'Shows the tax list on general settings without add, edit, or delete.',
        'setting.taxes.create' => 'Shows the add-tax button and creates a new rate. System default taxes stay protected.',
        'setting.taxes.update' => 'Edits the name and rate of a non-default tax.',
        'setting.taxes.delete' => 'Deletes a non-default tax. System taxes cannot be removed.',
        'setting.inventory costing.update' => 'Saves the costing method and can rebuild inventory cost. Affects accounting figures.',
        'setting.General setting.show' => 'Shows the currency / basic settings tab only — not the whole general settings page.',
        'setting.General setting.update' => 'Saves currency and basic settings on that tab only.',
        'setting.all.show' => 'Shows every Settings screen (tabs, tables, QR, and menu ratings). Company profile stays an Establishments permission.',
        'setting.menu_feedback.show' => 'Shows the menu ratings page in the sidebar. No create, edit, or delete.',
        'establishments.establishments.show' => 'Shows the Branches list in Settings. Create, edit, and delete a branch are a separate row.',
        'setting.all.update' => 'Saves on every Settings screen that has an edit action. Does not update the company profile.',
        'establishments.company.show' => 'Shows the company details tab (name, address, VAT, logo) read-only.',
        'establishments.company.update' => 'Saves company profile changes from the company details tab.',
        'establishments.establishment.delete' => 'Deletes a branch (soft or force, depending on state). Not granted with view alone.',
        'dashboard.Dashboard.show' => 'Shows the home overview on the main dashboard. Does not by itself open module dashboards.',
        'my_companies.My companies.show' => 'Opens My companies (/my-companies) from the sidebar. Switching from the navbar does not need this permission.',
        'referrals.Referrals.show' => 'Opens Share and earn (/referrals): invite link, stats, and copy.',
        'referrals.Referrals.create' => 'Sends Share and earn invites by email from the same page.',
        'Franchise Companies.Companies.show' => 'Shows the franchise companies tab and list without add or edit.',
        'Franchise Companies.Products.update' => 'Saves product grants and approves or rejects franchise product requests.',
        'Franchise Companies.Menus.update' => 'Saves custom-menu grants for a franchise company.',
        'reports_module.all.show' => 'Opens every report on the general reports page. Individual report rows can still restrict access.',
        'reports_module.Sell payment report.show' => 'Shows the sales payment report from the general reports page without export.',
        'reports_module.Sell payment report.print' => 'Exports the sales payment report as Excel or PDF.',
    ],
];
