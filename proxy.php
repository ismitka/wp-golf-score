<?php
/*
 * The MIT License
 *
 * Copyright 2024 Ivan Smitka <ivan at stimulus dot cz>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 *
 */

if (\PHP_VERSION_ID < 80000) {
	interface Stringable
	{
		/**
		 * @return string
		 */
		public function __toString();
	}
}

/**
 * https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=lat&lon=lon
 */
class Proxy implements Stringable {
	/**
	 * @var float
	 */
	private $lat;

	/**
	 * @var float
	 */
	private $lon;

	/**
	 * @param float $lat
	 * @param float $lon
	 */
	public function __construct($lat, $lon ) {
		$this->lat = $lat;
		$this->lon = $lon;
	}

	public function __toString(): string {
		return "LAT: {$this->lat}, LON: {$this->lon}";
	}

	private function getStorePath(): string {
		$path = __DIR__ . "/data";
		if ( ! is_dir( $path ) ) {
			mkdir( $path, 0755 );
		}

		return "{$path}/F_{$this->lat}_{$this->lon}.json";
	}

	public function getData(): array {
		$storePath = $this->getStorePath();
		if ( file_exists( $storePath ) ) {
			$data = json_decode( file_get_contents( $storePath ), true );
			if ( $data["ts"] < ( time() - ( 60 * 30 ) ) ) {
				return $data;
			}
			$data["forecast"] = array_filter( $data["forecast"], function ( $key ) {
				return $key > ( time() - ( 2 * 24 * 60 * 60 ) ); // 2 days
			}, ARRAY_FILTER_USE_KEY );
		} else {
			$data = [
				"forecast" => []
			];
		}

		if ( $this->update( $data ) ) {
			$this->store( $data );
		}

		return $data;
	}

	private function store( $data ): void {
		$storePath = $this->getStorePath();
		file_put_contents( $storePath, json_encode( $data ) );
	}

	private function update( &$data ): bool {
		$url = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat={$this->lat}&lon={$this->lon}";
		// request data
		$curlSession = curl_init();
		curl_setopt( $curlSession, CURLOPT_URL, $url );
		curl_setopt( $curlSession, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curlSession, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36" );

		$httpResponse = curl_exec( $curlSession );
		curl_close( $curlSession );

		$jsonData = json_decode( $httpResponse );

		if ( $ts = strtotime( $jsonData->properties->meta->updated_at ) ) {
			if ( ! array_key_exists( "ts", $data ) || $ts > $data["ts"] ) {
				foreach ( $jsonData->properties->timeseries as $timesery ) {
					if ( isset( $timesery->data->next_1_hours ) ) {
						$key                            = strtotime( $timesery->time );
						$data["forecast"][ $key ]["ts"] = $key;
						foreach ( [ "air_temperature", "wind_speed", "precipitation_amount", "cloud_area_fraction", "probability_of_thunder" ] as $property ) {
							$data["forecast"][ $key ][ $property ] = $timesery->data->instant->details->$property ?? null;
						}
						$data["forecast"][ $key ]["precipitation_amount"] = $timesery->data->next_1_hours->details->precipitation_amount;
					}
				}
				$data["ts"] = $ts;

				return true;
			}
		}

		return false;
	}
}

$lat = floatval( $_GET["lat"] );
$lon = floatval( $_GET["lon"] );

header( 'Cache-Control: no-cache, must-revalidate' );
header( 'Content-Type: application/json; charset=utf-8' );
header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );

$proxy = new Proxy( $lat, $lon );

$data     = $proxy->getData();
$response = json_encode( $data );
echo $response;
