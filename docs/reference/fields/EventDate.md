---
title: EventDate
---

# Class EventDate

<small class="block">Extends <span class="code">[Field](https://docs.craftcms.com/api/v5/craft-base-field.html '\\craft\\base\\Field')</span>, Implements <span class="code">[PreviewableFieldInterface](https://docs.craftcms.com/api/v5/craft-base-previewablefieldinterface.html '\\craft\\base\\PreviewableFieldInterface')</span>, <span class="code">[SortableFieldInterface](https://docs.craftcms.com/api/v5/craft-base-sortablefieldinterface.html '\\craft\\base\\SortableFieldInterface')</span></small>

## Constants

### ALL_DAY_NEVER

Events always have start and end times

### ALL_DAY_ALWAYS

Events always last all day

### ALL_DAY_OPTIONAL

Authors choose whether each event lasts all day

## Properties

### allowNeverEnding

```php
public bool $allowNeverEnding
```

### allDayMode

Whether events last all day (one of the `ALL_DAY_*` constants)

```php
public string $allDayMode
```

## Methods

### displayName

```php
public static displayName(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |

### icon

```php
public static icon(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |

### dbType

```php
public static dbType(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |

### phpType

```php
public static phpType(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |

### __construct

```php
public __construct(mixed $config = []): mixed
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| `$config`  | <span class="code">mixed</span> |     |
| **return** | <span class="code">mixed</span> |     |

### getElementValidationRules

```php
public getElementValidationRules(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |

### serializeValue

```php
public serializeValue(?EventDate $value, ?ElementInterface $element): ?array
```

|            |                                                                                                                                                    |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">?[EventDate](../models/EventDate.md '\\boundstate\\eventful\\models\\EventDate')</span>                                         |     |
| `$element` | <span class="code">?[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">?array</span>                                                                                                                   |     |

### queryCondition

```php
public static queryCondition(array $instances, mixed $value, array& $params): array|string|ExpressionInterface|false|null
```

|              |                                                                                                                                                                                   |     |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$instances` | <span class="code">array</span>                                                                                                                                                   |     |
| `$value`     | <span class="code">mixed</span>                                                                                                                                                   |     |
| `$params`    | <span class="code">array</span>                                                                                                                                                   |     |
| **return**   | <span class="code">array\|string\|[ExpressionInterface](https://www.yiiframework.com/doc/api/2.0/yii-db-expressioninterface '\\yii\\db\\ExpressionInterface')\|false\|null</span> |     |

### normalizeValue

```php
public normalizeValue(mixed $value, ?ElementInterface $element): mixed
```

|            |                                                                                                                                                    |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                    |     |
| `$element` | <span class="code">?[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">mixed</span>                                                                                                                    |     |

### normalizeValueFromRequest

```php
public normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
```

|            |                                                                                                                                                    |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                    |     |
| `$element` | <span class="code">?[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">mixed</span>                                                                                                                    |     |

### getSortOption

```php
public getSortOption(): array
```

|            |                                 |     |
| ---------- | ------------------------------- | --- |
| **return** | <span class="code">array</span> |     |

### getPreviewHtml

```php
public getPreviewHtml(mixed $value, ElementInterface $element): string
```

|            |                                                                                                                                                   |     |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                   |     |
| `$element` | <span class="code">[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">string</span>                                                                                                                  |     |

### previewPlaceholderHtml

```php
public previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
```

|            |                                                                                                                                                    |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                    |     |
| `$element` | <span class="code">?[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">string</span>                                                                                                                   |     |

### getInputHtml

```php
public getInputHtml(mixed $value, ?ElementInterface $element = null): string
```

|            |                                                                                                                                                    |     |
| ---------- | -------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                    |     |
| `$element` | <span class="code">?[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">string</span>                                                                                                                   |     |

### getStaticHtml

```php
public getStaticHtml(mixed $value, ElementInterface $element): string
```

|            |                                                                                                                                                   |     |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | --- |
| `$value`   | <span class="code">mixed</span>                                                                                                                   |     |
| `$element` | <span class="code">[ElementInterface](https://docs.craftcms.com/api/v5/craft-base-elementinterface.html '\\craft\\base\\ElementInterface')</span> |     |
| **return** | <span class="code">string</span>                                                                                                                  |     |

### getSettingsHtml

```php
public getSettingsHtml(): string
```

|            |                                  |     |
| ---------- | -------------------------------- | --- |
| **return** | <span class="code">string</span> |     |
