/**
 The MIT License

 Copyright 2024 Ivan Smitka <ivan at stimulus dot cz>.

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 */

export namespace WpGolfScore {
    interface Cfg {
        lat: number,
        lon: number
    }

    interface Forecast {
        ts: number,
        air_temperature: number,
        wind_speed: number,
        precipitation_amount: number,
        cloud_area_fraction: number,
        probability_of_thunder: number,
    }

    interface Score {
        dprev: number,
        d0: number,
        d1: number,
        d2: number,

        [key: string]: number
    }

    const getElements = (): NodeListOf<HTMLElement> => {
        //console.log("Check for Elements")
        return document.querySelectorAll<HTMLElement>('[data-golf-score]');
    };

    async function getForecast(cfg: Cfg): Promise<Forecast[]> {
        //console.log("getForecast", cfg);
        return await fetch("/wp-content/plugins/wp-golf-score/proxy.php?lat=" + cfg.lat + "&lon=" + cfg.lon)
            .then(response => response.json())
            .then(data => data.forecast);
    };

    const mapValue = (limits: number[], values: number[], value: number) => {
        let response = values[0];
        limits.forEach((v, index) => {
            if (value > v) {
                response = values[index + 1];
            }
        });
        return response;
    };

    const calculateScore = (dayOfWeek: number, month: number, temp: number, clouds: number, rain: number, thunder: number, wind: number): number => {
        const dayOfWeekScore = [9, 1, 4, 5, 5, 8, 10][dayOfWeek] * 4;
        const monthScore = ([1, 1, 5, 8, 10, 10, 8, 7, 8, 6, 3, 1][month]) * 7;
        const tempScore = mapValue([10, 20, 30, 35], [1, 3, 10, 8, 5], temp) * 10
        const windScore = mapValue([8, 14], [10, 8, 3], wind) * 6;
        const cloudsScore = mapValue([30, 60], [9, 10, 7], clouds) * 3;
        const rainScore = mapValue([0, 2.5, 8], [10, 6, 5, 1], rain) * 10;
        const thunderScore = mapValue([10, 40], [10, 6, 1], thunder) * 10;
        const score = dayOfWeekScore + monthScore + tempScore + windScore + cloudsScore + rainScore + thunderScore;

        console.log("calculateScore for", dayOfWeek, month, temp, wind, rain, clouds, thunder);
        console.log("calculateScore", dayOfWeek, month, temp, wind, rain, clouds, thunder);
        return Math.round(score / 50);
    };

    const getScore = (forecast: Forecast[]): Score => {
        const score: Score = {
            dprev: 5,
            d0: 5,
            d1: 5,
            d2: 5,
        };
        let dd;
        //console.log("getScore", forecast);
        for (dd = -1; dd <= 2; dd++) {
            const date = new Date();
            date.setMilliseconds(0);
            date.setHours(0);
            date.setMinutes(0);
            date.setSeconds(0);
            date.setDate(date.getDate() + dd);

            const start = Math.floor(date.getTime() / 1000);
            const end = start + (24 * 60 * 60);
            //console.log(start, end);

            const dayForecast = Object.values(forecast).filter((f) => {
                if (f.ts >= start && f.ts <= end) {
                    const date = new Date(f.ts * 1000);
                    return date.getHours() >= 8 && date.getHours() <= 20;
                }
                return false;
            });
            if (dayForecast.length > 0) {
                //console.log("dayForecast", dayForecast);

                const dayOfWeek = date.getDay(); // Sun 0
                const month = date.getMonth(); // 0 - 11
                const temp = Math.max(...dayForecast.map((f): number => {
                    return f.air_temperature;
                }));
                const clouds = dayForecast.map((f): number => {
                    return f.cloud_area_fraction ?? 100;
                }).reduce((previousValue, currentValue) => {
                    return previousValue + currentValue;
                }) / dayForecast.length;
                const wind = Math.max(...dayForecast.map((f): number => {
                    return f.wind_speed ?? 0;
                }));
                const rain = Math.max(...dayForecast.map((f): number => {
                    return f.precipitation_amount ?? 0;
                }));
                const thunder = Math.max(...dayForecast.map((f): number => {
                    return f.probability_of_thunder ?? 0;
                }));

                switch (dd) {
                    case -1:
                        score.dprev = calculateScore(dayOfWeek, month, temp, clouds, rain, thunder, wind);
                        break;
                    default:
                        score["d" + dd] = calculateScore(dayOfWeek, month, temp, clouds, rain, thunder, wind);
                        break;
                }
            } else {
                console.warn("Forecast not available", start, end);
            }
        }
        return score;
    };

    const processScore = (element: HTMLElement, score: Score): void => {
        //console.log("processScore", element, score);
        let i;
        let prevScore = score.dprev;
        const empty = element.querySelectorAll<HTMLElement>("[data-day]").length === 0;
        for (i = 0; i <= 2; i++) {
            const dayScore = score["d" + i];
            let target = element.querySelector<HTMLElement>("[data-day='" + i + "']");
            if (target === null && empty) {
                target = document.createElement("span");
                target.setAttribute("data-day", i.toString());
                element.append(target);
            }
            if (target !== null) {
                if (element.getAttribute("data-date-element") === "1") {
                    target.classList.add("with-date");
                    let dateElement = target.querySelector<HTMLSpanElement>("[data-date]");
                    if (dateElement === null) {
                        dateElement = document.createElement("span");
                        dateElement.setAttribute("data-date", "1");
                        target.append(dateElement);
                    }
                    let date = new Date();
                    date.setDate(date.getDate() + i);
                    dateElement.textContent = date.toLocaleString("cs-CZ", {
                        weekday: "short",
                        month: "numeric",
                        day: "numeric",
                    });
                } else {
                    target.classList.remove("with-date");
                }

                const icon = "/wp-content/plugins/wp-golf-score/static/img/" + (dayScore > prevScore ? "up" : dayScore < prevScore ? "dn" : "eq") + ".png";
                let iconElement = target.querySelector<HTMLImageElement>("img");
                if (iconElement === null) {
                    iconElement = document.createElement("img");
                    iconElement.setAttribute("src", icon);
                    target.append(iconElement);
                }

                let scoreElement = target.querySelector<HTMLSpanElement>("[data-score]");
                if (scoreElement === null) {
                    scoreElement = document.createElement("span");
                    scoreElement.setAttribute("data-score", "");
                    target.append(scoreElement);
                }
                scoreElement.textContent = dayScore.toString();
            }
            prevScore = dayScore;
        }
    };

    const doUpdate = async (): Promise<void> => {
        //console.log("doUpdate");
        const elements = getElements();
        //console.log(elements);
        for (const element of elements) {
            const cfgS = element.dataset.golfScore;
            //console.log(cfgS);
            if (typeof cfgS === "string") {
                try {
                    const cfg: Cfg = JSON.parse(cfgS);
                    //console.log(cfg);
                    if (cfg.lat && cfg.lon) {
                        getForecast(cfg).then((forecast) => {
                                //console.log(forecast);
                                const score = getScore(forecast);
                                processScore(element, score);
                            }
                        );
                    }
                } catch (e) {
                    console.error("Can't parse wp-golf-score config");
                }
            }
        }
    };

    export const init = () => {
        let initInterval: number | undefined = setInterval(() => {
            if (getElements().length > 0) {
                if (initInterval !== undefined) {
                    clearInterval(initInterval);
                    doUpdate();
                    setInterval(() => {
                        doUpdate();
                    }, 30 * 60 * 1000);
                }
            }
        }, 1000); // every 30min.
    }
}

WpGolfScore.init();