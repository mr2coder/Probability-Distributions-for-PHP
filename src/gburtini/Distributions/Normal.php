<?php
	/*
	 * Normal Distribution - Statistical Distributions for PHP
	 *
	 * Copyright (C) 2015 Giuseppe Burtini <joe@iterative.ca>. 
	 *
	 * Other credits
	 * Box, Muller 1958 for the rand() method
	 * Michael Nickerson (2004), Thomas Ziegler for the icdf function.
	 */
	namespace gburtini\Distributions;
	use gburtini\Distributions\Distribution;
	use gburtini\Distributions\T;
	class Normal extends Distribution {
		// TODO: implement a skewness, kurtosis normal.
		protected $mean;
		protected $variance;
		protected $skewness;
		protected $kurtosis;

		public function __construct($mean = 0, $variance = 1, $skewness=0, $kurtosis=0) {
			static::validateParameters($mean, $variance, $skewness, $kurtosis);

			$this->mean = floatval($mean);
			$this->variance = floatval($variance);
			//$this->skewness = floatval($skewness);
			//$this->kurtosis = floatval($kurtosis);
		}

		public function sampleConfidenceInterval($sampleSize, $gamma=0.95, $t=true) {
			if(!$t) {
				return $this->zConfidenceInterval($gamma);
			} else {
				return $this->studentConfidenceInterval($sampleSize, $gamma);
			}
		}
		protected function zConfidenceInterval($gamma) { throw new \BadMethodCallException("Z-approximated normal sample confidence interval isn't implemented.");  }
		protected function studentConfidenceInterval($sampleSize, $gamma) {
			$t = new T($sampleSize - 1);
			$tstat = $t->icdf($gamma);
			$adjusteds = sqrt($this->variance)/sqrt($sampleSize);
			return [$this->mean - ($tstat * $adjusteds), $this->mean + ($tstat * $adjusteds)];
		}

		public function rand() {
			return static::draw($this->mean, $tihs->variance);

		}
		public static function draw($mean, $variance) {
			return static::boxMuller()*sqrt($variance) + $mean;
		}
		public function cdf($x) {
		        $d = $x - $this->mean;
		        return 0.5 * (1 + $this->erf($d / (sqrt($this->variance) * sqrt(2))));
		}
		public function icdf($y) {
			// Inverse ncdf approximation by Peter John Acklam, implementation adapted to
			// PHP by Michael Nickerson, using Dr. Thomas Ziegler's C implementation as
			// a guide.  http://home.online.no/~pjacklam/notes/invnorm/index.html
			// I have not checked the accuracy of this implementation.  Be aware that PHP
			// will truncate the coeficcients to 14 digits.

			// You have permission to use and distribute this function freely for
			// whatever purpose you want, but please show common courtesy and give credit
			// where credit is due.

			// Input paramater is $p - probability - where 0 < p < 1.

			// Coefficients in rational approximations
			$a = array(1 => -3.969683028665376e+01, 2 => 2.209460984245205e+02,
				3 => -2.759285104469687e+02, 4 => 1.383577518672690e+02,
				5 => -3.066479806614716e+01, 6 => 2.506628277459239e+00);

			$b = array(1 => -5.447609879822406e+01, 2 => 1.615858368580409e+02,
				3 => -1.556989798598866e+02, 4 => 6.680131188771972e+01,
				5 => -1.328068155288572e+01);

			$c = array(1 => -7.784894002430293e-03, 2 => -3.223964580411365e-01,
				3 => -2.400758277161838e+00, 4 => -2.549732539343734e+00,
				5 => 4.374664141464968e+00, 6 => 2.938163982698783e+00);

			$d = array(1 => 7.784695709041462e-03, 2 => 3.224671290700398e-01,
				3 => 2.445134137142996e+00, 4 => 3.754408661907416e+00);

			// Define break-points.
			$p_low =  0.02425;	//Use lower region approx. below this
			$p_high = 1 - $p_low;	//Use upper region approx. above this

			// Define/list variables (doesn't really need a definition)
			// $p (probability), $sigma (std. deviation), and $mu (mean) are user inputs
			$q = NULL; $x = NULL; $y = NULL; $r = NULL;

			// Rational approximation for lower region.
			if (0 < $p && $p < $p_low) {
				$q = sqrt(-2 * log($p));
				$x = ((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5]) *
					$q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) * $q + $d[4]) *
					$q + 1);
				}

			// Rational approximation for central region.
				elseif ($p_low <= $p && $p <= $p_high) {
					$q = $p - 0.5;
					$r = $q * $q;
					$x = ((((($a[1] * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) *
						$r + $a[6]) * $q / ((((($b[1] * $r + $b[2]) * $r + $b[3]) * $r +
							$b[4]) * $r + $b[5]) * $r + 1);
					}

			// Rational approximation for upper region.
					elseif ($p_high < $p && $p < 1) {
						$q = sqrt(-2 * log(1 - $p));
						$x = -((((($c[1] * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q +
							$c[5]) * $q + $c[6]) / (((($d[1] * $q + $d[2]) * $q + $d[3]) *
							$q + $d[4]) * $q + 1);
						}

			// If 0 < p < 1, return a null value
						else {
							$x = NULL;
						}

						return $x;
			// END inverse ncdf implementation.
		}

		// TODO: Ziggurat algorithm to draw values: https://en.wikipedia.org/wiki/Ziggurat_algorithm
		public static function boxMuller() {
			// Box-Muller transform (Box, Muller 1958).
			$u = mt_rand()/mt_getrandmax();
			$v = mt_rand()/mt_getrandmax();

			return sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
		}

		public static function validateParameters($m, $v, $s, $k) {
			if(!is_numeric($m) || !is_numeric($v) || !is_numeric($s) || !is_numeric($k))
				throw new \InvalidArgumentException("Non-numeric parameter in normal distribution (" . static::renderParameters(compact($m,$v,$s,$k)) . ").");
			
			if($v < 0)
				throw new \InvalidArgumentException("Variance must be strictly positive (it is σ-squared after all!). Currently, \$v = " . var_export($v) . ".");
			
		}
		
		protected static function erf($x) {
			    // translation of Press, Teukolsky, Vetterling, Flannery (2001) approximation.
			    // https://en.wikipedia.org/wiki/Error_function#Numerical_approximation 
			$t = 1 / (1 + 0.5 * abs($x));
			$tau = $t * exp(
			        - $x * $x
			        - 1.26551223
			        + 1.00002368 * $t
			        + 0.37409196 * pow($t, 2)
			        + 0.09678418 * pow($t, 3)
			        - 0.18628806 * pow($t, 4)
			        + 0.27886807 * pow($t, 5)
			        - 1.13520398 * pow($t, 6)
			        + 1.48851587 * pow($t, 7)
			        - 0.82215223 * pow($t, 8)
			        + 0.17087277 * pow($t, 9));
			
			if ($x >= 0) {
			    return 1 - $tau;
			} else {
			    return $tau - 1;
			}
		}
	}
?>
