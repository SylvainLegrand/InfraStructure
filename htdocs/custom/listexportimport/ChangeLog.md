# CHANGELOG LISTEXPORTIMPORT FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.7] - 2026-04-10
### Changed
- FIX compatibility (some error)

## [2.0.6] - 2026-03-26
### Changed
- FIX compatibility + SQL export forbidden error

## [2.0.5] - 2025-09-21
### Changed
- Add de_DE, ro_RO language

### Changed
- Review support/about page

### Fixed
- Error on PDF export of accountancy
- Warning function deprecated

## [2.0.4] - 2024-12-19
### Fixed
- Fixed PDF export when select column on left

## [2.0.3] - 2024-11-19
- Fixed add date to PDF option

## [2.0.2] - 2024-11-08
- Cleaned code

## [2.0.1] - 2024-10-25
- Module taken over by Inovea Conseil

## [2.0.0] - MAR21
- NEW - Compatibility with select in table
- FIX - Generate PDF when too many columns
- FIX - V16 CSRF Token

## [1.3.1] - OCT17
- Fix: Hide buttons when optioncss == print.

## [1.3.0] - OCT17
- Fix: better solution for the truncated cells in PDF format (break line).

## [1.2.9] - OCT17
- Fix: data truncated on PDF format (pdf page will now be too large as needed).
- Fix: conflict with the volume calculator table (was affecting CSV format only).
- New: Add warning icon for SQL & CSV from DB export formats, to indicate that the entire list will be exported.

Notes:
- Re-enable module required.
