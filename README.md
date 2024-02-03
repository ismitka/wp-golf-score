# wp-golf-score
Golf Feeling Score - WordPress Plugin

Calculate Golf Feeling Score depends on weather forecast and Season.\
Script periodically sets css class for elements with attribute `data-day`

class
* .score-1 to .score-10
* .diff-eq, .diff-up, diff-down
* .diff-up-1 to .diff-up-9
* .diff-down-1 to .diff-down-9

Example:

Show Golf Feeling Score - full config
```html
<span data-golf-score='{"lat": 0, "lon": 0}' data-date-element="1">
    <span data-day="0">
        <span data-date></span>
    </span>
    <span data-day="1">
        <span data-date></span>
    </span>
    <span data-day="2">
        <span data-date></span>
    </span>
</span>
```

Simple Use. Inner elements will be added automatically

```html
    <span data-golf-score='{"lat": 0, "lon": 0}'></span>
```

Today Only

```html
<span data-golf-score='{"lat": 0, "lon": 0}'></span>
    <span data-day="0"></span>
</span>
```

