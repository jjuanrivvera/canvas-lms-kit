# Feature: Implement Query Builder for fluent API queries

**Labels:** `enhancement`, `good first issue`

## Description

Implement a Query Builder pattern to provide a more fluent and intuitive way to build complex API queries. This would enhance the developer experience by allowing chainable methods for filtering, sorting, and pagination.

## Motivation

Currently, users must pass arrays with query parameters:

```php
$courses = Course::get([
    'enrollment_state' => 'active',
    'sort' => 'name',
    'order' => 'asc',
    'per_page' => 50
]);
```

A Query Builder would provide a more expressive and discoverable API:

```php
$courses = Course::query()
    ->where('enrollment_state', 'active')
    ->orderBy('name', 'asc')
    ->limit(50)
    ->get();
```

## Benefits

- **Better IDE Support**: Method signatures provide autocomplete and type hints
- **More Discoverable**: Methods are easier to discover than array keys
- **Type Safety**: Validate parameters at build time instead of runtime
- **Chainable**: Compose complex queries step by step
- **Maintainable**: Changes to query logic are easier to track

## Proposed API

```php
// Basic queries
$courses = Course::query()
    ->where('state', 'available')
    ->get();

// Multiple conditions
$users = User::query()
    ->where('enrollment_type', 'student')
    ->where('enrollment_state', 'active')
    ->get();

// Sorting
$courses = Course::query()
    ->orderBy('name')
    ->orderBy('created_at', 'desc')
    ->get();

// Pagination
$result = Course::query()
    ->where('state', 'available')
    ->limit(25)
    ->page(2)
    ->paginate();

// Select specific fields (if Canvas supports it)
$courses = Course::query()
    ->select(['id', 'name', 'course_code'])
    ->get();

// Date ranges
$submissions = Submission::query()
    ->whereBetween('submitted_at', '2024-01-01', '2024-12-31')
    ->get();

// Complex queries
$enrollments = Enrollment::query()
    ->where('type', 'StudentEnrollment')
    ->where('state', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();
```

## Implementation Tasks

- [ ] Create `QueryBuilder` base class
- [ ] Implement `where()` method with operators support
- [ ] Implement `orderBy()` / `orderByDesc()` methods
- [ ] Implement `limit()` / `take()` methods
- [ ] Implement `page()` method for pagination
- [ ] Implement `select()` for field selection
- [ ] Add `whereBetween()`, `whereIn()`, `whereNotIn()` helpers
- [ ] Integrate with existing `get()`, `all()`, `paginate()` methods
- [ ] Add query builder to all API classes via trait
- [ ] Write comprehensive tests
- [ ] Update documentation with examples
- [ ] Consider query caching/memoization

## Technical Considerations

1. **Canvas API Limitations**: Ensure the query builder respects Canvas API constraints
2. **Backward Compatibility**: Maintain existing array-based API
3. **Performance**: Lazy evaluation - build query but don't execute until terminal method
4. **Type Safety**: Use PHPStan to validate query builder usage
5. **Extensibility**: Allow custom query builders for specific APIs

## Example Implementation Sketch

```php
class QueryBuilder
{
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $page = null;

    public function where(string $field, mixed $value): self
    {
        $this->wheres[$field] = $value;
        return $this;
    }

    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $this->orders[] = ['field' => $field, 'direction' => $direction];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            ...$this->wheres,
            'per_page' => $this->limit,
            'page' => $this->page,
            // ... build order_by params
        ]);
    }

    public function get(): array
    {
        return static::getApiClass()::get($this->toArray());
    }

    public function paginate(): PaginationResult
    {
        return static::getApiClass()::paginate($this->toArray());
    }
}
```

## Related Issues

This relates to the broader goal of improving developer experience and making the SDK more intuitive for complex operations.

## Priority

**Medium-High** - This is a quality of life improvement that would significantly enhance the developer experience, especially for complex queries.

## Timeline

Estimated 2-4 weeks for full implementation including tests and documentation.
