# wp-golf-score
Golf Feeling Score - WordPress Plugin

Calculate Golf Feeling Score depends on weather forecast and Season.\
Script periodically sets css class for elements with attribute `data-day`

Example:

Show Golf Feeling Score - full config short-code
```text
# syntax
[wp-golf-score lat=float lon=float [class=string] [date=boolean] [days=int]]
# minimal
[wp-golf-score lat=50.2 lon=14.678]
# full
[wp-golf-score lat=50.2 lon=14.678 class="header" date=false days=3]
```
parameters:

* lat/lon: geo coordinates of forecasted place
* days: 1-3 calculate score for n following days (include today)
* date: true/false include date
* class: any custom css class. "small-img" special class, image same size as score index

Build CSS 
```bash
sass src/golf-score.scss:static/golf-score.css
```

Update dependencies
```bash
npm install
```

Compile JS
```bash
pnpm run build
```

Create plugin archive
```bash
./compress.sh
```