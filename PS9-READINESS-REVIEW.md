# PrestaShop 9 Readiness Review: changedefaultcombination

**Module:** Change Default Combination When Out of Stock  
**Current Version:** 1.0.0  
**Review Date:** September 16, 2026  
**Reviewer:** Cloud Agent  

---

## Executive Summary

This module is **NOT ready for PrestaShop 9** without significant updates. While the core business logic is sound, multiple critical compatibility issues prevent it from running on PS9 (PHP 8.1+, Symfony 6).

**Overall Risk Level:** 🔴 **HIGH** - Multiple blockers present

---

## Critical Issues (BLOCKERS)

### 1. PHP Version Constraint ⛔
**File:** `composer.json` (line 10)  
**Current:** `"php": ">=5.6.0"`  
**Required:** `"php": ">=8.1.0"`  

PrestaShop 9 requires PHP 8.1+. The current constraint allows PHP 5.6-8.0 but excludes PHP 8.1+.

**Impact:** Module will not install on PS9 shops.

---

### 2. Placeholder Namespace Configuration ⛔
**Files:** `composer.json` (line 14-16), `vendor/composer/autoload_psr4.php` (line 9)  
**Current:** `"<YourNamespace>\\": "src/"`  
**Actual Namespace in Code:** `PrestaShop\Module\ChangeDefaultCombination\`

The composer autoload configuration uses a literal placeholder string `<YourNamespace>\` instead of the actual namespace used in the code.

**Impact:** Autoloading is completely broken. The `CombinationSelector` class cannot be loaded, causing fatal errors when the hook fires.

**Evidence:** 
- `changedefaultcombination.php` line 27: `use PrestaShop\Module\ChangeDefaultCombination\Selector\CombinationSelector;`
- `src/Selector/CombinationSelector.php` line 3: `namespace PrestaShop\Module\ChangeDefaultCombination\Selector;`

---

### 3. Missing Use Statements in Namespaced Class 🔴
**File:** `src/Selector/CombinationSelector.php`  
**Lines:** 8, 10, 12, 21, 23

The `CombinationSelector` class uses PrestaShop core classes without importing them:
- `Product` (line 8, 11, 23)
- `Configuration` (line 10)
- `StockAvailable` (line 12, 21)

**Impact:** Fatal errors: "Class 'PrestaShop\Module\ChangeDefaultCombination\Selector\Product' not found"

**Required Fix:** Add at top of file:
```php
use Configuration;
use Product;
use StockAvailable;
```

---

### 4. Compatibility Declaration Too Narrow ⚠️
**File:** `changedefaultcombination.php` (line 55)  
**Current:** `'min' => '1.7', 'max' => '8.99.99'`  
**Issue:** Explicitly excludes PrestaShop 9.x

**Impact:** Module appears incompatible in PS9 back office; cannot be installed.

**Fix:** After resolving other issues, update to `'max' => '9.99.99'` or remove max entirely.

---

## Major Issues (SHOULD FIX)

### 5. Missing config.xml Compatibility Declaration 📋
**File:** `config.xml`  
**Issue:** No `<ps_versions_compliancy>` element

While the PHP class defines compatibility (line 55 in main file), `config.xml` should also declare it for PrestaShop's native parser.

**PrestaShop 9 Pattern:**
```xml
<ps_versions_compliancy>
    <min>8.0.0</min>
    <max>9.99.99</max>
</ps_versions_compliancy>
```

---

### 6. Version Mismatch Across Files 📝
**Files:** `changedefaultcombination.php` (line 41), `config.xml` (line 4), `config_pl.xml` (line 4)

- Main module class: `1.0.0`
- config.xml: `0.0.1`
- config_pl.xml: `0.0.1`

**Impact:** Confusion during updates; PrestaShop may not recognize updates correctly.

---

### 7. Raw SQL Without Escaping/Validation 🛡️
**File:** `changedefaultcombination.php` (lines 262-276)  
**Hook:** `hookActionUpdateQuantity`

Three raw UPDATE queries constructed with manual integer casting:
```php
$sql_product = 'UPDATE `' . _DB_PREFIX_ . 'product_shop`
SET `cache_default_attribute` = ' . (int)$bestAttribute_id . '
WHERE `id_product` = ' . (int)$id;
```

**Issues:**
- No transaction wrapping (3 queries should be atomic)
- No error handling
- Manual SQL string construction (PrestaShop 9 prefers QueryBuilder/Doctrine DBAL)

**Risk Level:** Medium (values are cast to int, but no failure handling)

**Modern PS9 Pattern:**
```php
try {
    $conn = Db::getInstance();
    $conn->beginTransaction();
    // queries...
    $conn->commit();
} catch (\Exception $e) {
    $conn->rollback();
    PrestaShopLogger::addLog(...);
}
```

---

### 8. Missing CSRF Protection in Form 🔒
**File:** `changedefaultcombination.php` (lines 87-88)  
**Method:** `getContent()`

Form submission uses `Tools::isSubmit()` without explicit CSRF token validation.

**Current:**
```php
if (((bool)Tools::isSubmit('submitChangedefaultcombinationModule')) == true) {
    $this->postProcess();
}
```

**PrestaShop 9 Best Practice:**
```php
if (Tools::isSubmit('submitChangedefaultcombinationModule')) {
    if ($this->context->controller->ajax && !$this->context->controller->token) {
        die($this->l('Invalid token'));
    }
    $this->postProcess();
}
```

HelperForm does generate tokens (`$helper->token` line 164), but explicit validation is missing.

---

### 9. Unnecessary Database Table 🗄️
**File:** `sql/install.php` (lines 28-31)

Creates an empty table `changedefaultcombination` with only an auto-increment ID. The table is never used in the module logic.

**Impact:** Dead code; wastes database resources.

**Recommendation:** Remove table creation or document its intended future use.

---

## Minor Issues (NICE TO HAVE)

### 10. Vendor Directory Committed to Git 📦
**Evidence:** Git history shows "zmiana gitignore dodanie vendor" commit, `.gitignore` is empty

**Issue:** `vendor/` contains only Composer's autoloader files (no third-party dependencies). Committing `vendor/` is an anti-pattern.

**Recommendation:**
```gitignore
/vendor/
```

Module installation should run `composer install` or rely on PrestaShop's autoloader.

---

### 11. Legacy HelperForm (Smarty-based) 🎨
**File:** `changedefaultcombination.php` (lines 100-173)

Uses PrestaShop 1.7-style `HelperForm` with Smarty template.

**PrestaShop 9 Recommendation:** Use Symfony Forms for back-office configuration. However, HelperForm still works in PS9 for backward compatibility.

**Priority:** Low (not blocking, but modernization would improve maintainability)

---

### 12. Incomplete Upgrade Script 🔄
**File:** `upgrade/upgrade-1.1.0.php`

Placeholder upgrade script with no actual logic (just returns `true`).

**Recommendation:** Either implement upgrade logic or remove the file if version 1.1.0 doesn't introduce breaking changes.

---

### 13. Unused/Dead Code in Main Class 💀
**File:** `changedefaultcombination.php` (lines 204-252)

Large commented-out block containing original implementation of combination selection logic.

**Recommendation:** Remove commented code (now implemented in `CombinationSelector` class). Keep git history for reference.

---

### 14. Missing Type Declarations 📐
**File:** `src/Selector/CombinationSelector.php`

Method `getBestCombination` has partial typing:
- Parameters: `int $id_product, int $setting` ✅
- Return type: `?array` ✅
- Local variables: No type hints ❌

**PHP 8.1+ Best Practice:** Add property types if class gains properties, strict_types declaration:
```php
declare(strict_types=1);
```

---

### 15. Template Contains Placeholder Text 📝
**File:** `views/templates/admin/configure.tpl` (lines 29-36)

Generic boilerplate text:
> "Here is my new generic module!"  
> "Thanks to PrestaShop, now I have a great module."

**Impact:** Unprofessional appearance in back office.

---

### 16. Hook Registration Without Usage 🪝
**File:** `changedefaultcombination.php` (lines 67-68)

Registers `header` and `displayBackOfficeHeader` hooks but never implements them (no `hookHeader()` or `hookDisplayBackOfficeHeader()` methods).

**Impact:** Unnecessary hook registration adds minor overhead.

**Recommendation:** Remove unused hook registrations.

---

### 17. No Symfony Services / Dependency Injection 🏗️
**Architecture Note**

Module uses classic PrestaShop 1.x architecture:
- Direct static calls (`Product::`, `Configuration::`, `StockAvailable::`)
- No service container
- No CQRS commands

**PrestaShop 9 Modern Pattern:** Register services in `config/services.yml`, inject dependencies.

**Priority:** Low for a simple module; static calls still work in PS9.

---

## Security Assessment 🔐

| Area | Status | Notes |
|------|--------|-------|
| SQL Injection | ✅ Good | Integer casting on all SQL inputs |
| XSS | ✅ Good | No direct output; uses PrestaShop's `$this->l()` |
| CSRF | ⚠️ Fair | HelperForm has tokens, but no explicit validation |
| Access Control | ✅ Good | Configuration requires admin login (HelperForm) |
| Data Validation | ⚠️ Fair | Setting value validated by form but not double-checked in hook |

---

## PrestaShop 9 API Compatibility ✅

### APIs Used (all still valid in PS9):
- ✅ `Module` base class
- ✅ `Configuration::get/updateValue/deleteByName`
- ✅ `Product::getDefaultAttribute/getPriceStatic`
- ✅ `StockAvailable::getQuantityAvailableByProduct`
- ✅ `HelperForm` (legacy but supported)
- ✅ `Db::getInstance()->execute()`
- ✅ `Tools::isSubmit/getValue`
- ✅ Hook `actionUpdateQuantity` (still fires in PS9)

No deprecated or removed APIs detected.

---

## Testing Recommendations 🧪

Before deploying to PS9:
1. ✅ Fix composer.json namespace
2. ✅ Add missing use statements
3. ✅ Update PHP requirement
4. ✅ Update ps_versions_compliancy
5. 🧪 Test in PS9 dev environment:
   - Product with combinations goes out of stock → verify default switches
   - Multiple products with different settings (highest price, lowest stock, etc.)
   - Verify no fatal errors in logs
6. 🧪 Check multistore/multishop scenarios (module uses `product_shop` table)

---

## File-by-File Summary

| File | Blocker | Should Fix | Nice to Have |
|------|---------|------------|--------------|
| `composer.json` | ⛔ Namespace, ⛔ PHP version | | 📦 Description placeholder |
| `changedefaultcombination.php` | 🔴 Max version | 🛡️ SQL safety, 🔒 CSRF | 💀 Dead code, 🪝 Unused hooks |
| `src/Selector/CombinationSelector.php` | ⛔ Missing use statements | | 📐 Type declarations |
| `config.xml` | | 📋 ps_versions_compliancy, 📝 Version | |
| `config_pl.xml` | | 📝 Version | |
| `sql/install.php` | | 🗄️ Unused table | |
| `upgrade/upgrade-1.1.0.php` | | | 🔄 Empty script |
| `views/templates/admin/configure.tpl` | | | 📝 Placeholder text |
| `.gitignore` | | | 📦 Vendor exclusion |

---

## Recommended Fix Priority

### Phase 1: Critical Path to PS9 Compatibility (Required)
1. **Fix composer.json autoload namespace**
   ```json
   "psr-4": {
     "PrestaShop\\Module\\ChangeDefaultCombination\\": "src/"
   }
   ```
2. **Update PHP requirement**
   ```json
   "php": ">=8.1.0"
   ```
3. **Add use statements to CombinationSelector.php**
   ```php
   use Configuration;
   use Product;
   use StockAvailable;
   ```
4. **Update ps_versions_compliancy**
   ```php
   'min' => '1.7', 'max' => '9.99.99'
   ```
5. **Regenerate composer autoloader**
   ```bash
   composer dump-autoload
   ```

**Estimated Effort:** 15 minutes  
**Test:** Install on PS9, trigger stock change, verify no fatal errors

---

### Phase 2: Production Hardening (Strongly Recommended)
6. Wrap SQL updates in transaction with error handling
7. Add explicit CSRF validation in `getContent()`
8. Synchronize version numbers across all files
9. Add ps_versions_compliancy to config.xml
10. Remove unused database table or document purpose

**Estimated Effort:** 1-2 hours

---

### Phase 3: Code Quality (Optional)
11. Remove dead code (commented blocks, unused hooks)
12. Add .gitignore for vendor/
13. Update admin template text
14. Add strict_types declaration
15. Consider Symfony Form migration (long-term modernization)

**Estimated Effort:** 2-3 hours

---

## Comparison with Other Modules

You mentioned three other modules for review:
- **opc** (One Page Checkout)
- **sellnetictheme** (Theme)
- **wkrestrictpayoutstanding** (Payment restriction)

### Recommendation: Review Order
1. ✅ **changedefaultcombination** (current) - Simple module, good learning case
2. **wkrestrictpayoutstanding** - Likely similar complexity (payment hooks)
3. **opc** - Higher complexity (checkout flow, front-end JS, may have Symfony components)
4. **sellnetictheme** - Different concerns (Twig/Smarty templates, theme.yml, PS9 theme structure)

**Suggested Next Step:** Review **wkrestrictpayoutstanding** next as it likely shares similar architectural patterns to changedefaultcombination (hooks, configuration, back-office forms). OPC and themes have different migration concerns.

---

## Conclusion

**Can this module run on PrestaShop 9 as-is?** ❌ **NO**

**Is it fixable?** ✅ **YES** - All issues are addressable, no architectural blockers.

**Minimum Viable Fix:** ~15 minutes (4 critical changes above)

**Production-Ready Fix:** ~3-4 hours total (all phases)

The module's core logic is solid and uses stable PrestaShop APIs. Once the namespace, PHP version, and use statements are corrected, it should function correctly on PS9.

---

**Review completed by:** Cursor Cloud Agent  
**For questions about this review, refer to specific line numbers cited above.**
