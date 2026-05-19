# htdocs/custom - External Modules

External modules are installed in `htdocs/custom/`. This directory is preserved during Dolibarr upgrades.

## Module Builder

Use `/dolibarr-new-module` skill to create a new module. Built-in GUI at **Home > Developer Tools > Module Builder** (Dolibarr ≥ 12.0).

## Module Structure

```
custom/mymodule/
├── core/
│   ├── lib/
│   │   ├── mymodule.lib.php            # Helper functions (/dolibarr-lib)
│   │   └── mymoduleAdmin.lib.php       # Helper functions (/dolibarr-lib)
│   ├── modules/
│   │   ├── modMyModule.class.php       # Module descriptor (/dolibarr-module-descriptor)
│   │   └── mymodule/                   # Numbering & PDF models (/dolibarr-pdf-template)
│   ├── boxes/                          # Dashboard widgets (/dolibarr-widgets)
│   ├── triggers/                       # Event triggers (/dolibarr-triggers)
│   ├── substitutions/                  # Variable substitutions (/dolibarr-substitutions)
│   └── tpl/                            # Core template overrides (/dolibarr-tpl)
├── class/
│   ├── myobject.class.php              # Business object (/dolibarr-class-conventions)
│   ├── mymoduleutils.class.php         # Cron jobs & utilities (/dolibarr-cron)
│   ├── api_mymodule.class.php          # REST API (/dolibarr-api-development)
│   └── actions_mymodule.class.php      # Hooks (/dolibarr-hooks)
├── langs/en_US/mymodule.lang           # Translations (/dolibarr-translation)
├── sql/
│   ├── llx_mymodule_myobject.sql       # Table creation (/dolibarr-sql-schema)
│   ├── llx_mymodule_myobject.key.sql   # Indexes (/dolibarr-sql-schema)
│   └── update...
├── ajax/                               # Ajax request handlers (/dolibarr-ajax)
├── css/                                # Stylesheets (/dolibarr-css)
├── img/                                # Icons and images
├── js/                                 # JavaScript code (/dolibarr-js)
├── scripts/                            # CLI scripts
├── test/                               # Unit tests (/dolibarr-testing)
├── admin/
│   ├── about.php                       # Module about page
│   ├── changelog.php                   # Module changelog page
│   └── mymodulesetup.php               # Module settings (/dolibarr-page-patterns)
├── myobject_card.php                   # Card page (/dolibarr-page-patterns)
├── myobject_list.php                   # List page (/dolibarr-page-patterns)
├── docs/changeLog.xml                  # Fixes and evolutions log (/dolibarr-changelog)
├── README.md                           # User documentation (functional)
└── config.php                          # PHP/Dolibarr environment
```

## Upgrade Safety

- For upgrade-safe customizations, do not modify core files in `htdocs/`; use `htdocs/custom/`
- Use hooks instead of editing core pages (/dolibarr-hooks)
- Use triggers instead of editing core classes (/dolibarr-triggers)
- Use extrafields instead of adding database columns (/dolibarr-extrafields)

## Documentation

- https://wiki.dolibarr.org/index.php/Module_development
- https://wiki.dolibarr.org/index.php/Module_Builder
