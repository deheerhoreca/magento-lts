<?php

// @url https://gist.github.com/esyede/aac7275ef7b8e4dc03c41c11cfe41afb

if (PHP_VERSION_ID >= 801000 && !function_exists('strftime')) {
    function strftime($format, $timestamp = null, $locale = null)
    {
        if (!extension_loaded('intl')) {
            throw new \RuntimeException('The php-intl extension needs to be loaded to be able to use this polyfill');
        }

        if (!($timestamp instanceof \DateTimeInterface)) {
            $timestamp = is_int($timestamp) ? '@' . $timestamp : (string) $timestamp;

            try {
                $timestamp = new \DateTime($timestamp, new \DateTimeZone(date_default_timezone_get()));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    '$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.'
                );
            }
        }

        $locale = preg_replace('/[^\w-].*$/', '', empty($locale) ? setlocale(LC_TIME, '0') : $locale);
        $intlFormats = ['%a' => 'EEE', '%A' => 'EEEE', '%b' => 'MMM', '%B' => 'MMMM', '%h' => 'MMM'];
        $intlFormatter = function (\DateTimeInterface $timestamp, $format) use ($intlFormats, $locale) {
            $tz = $timestamp->getTimezone();
            $dateType = \IntlDateFormatter::FULL;
            $timeType = \IntlDateFormatter::FULL;
            $pattern = '';

            switch ($format) {
                case '%c':
                    $dateType = \IntlDateFormatter::LONG;
                    $timeType = \IntlDateFormatter::SHORT;
                    break;

                case '%x':
                    $dateType = \IntlDateFormatter::SHORT;
                    $timeType = \IntlDateFormatter::NONE;
                    break;

                case '%X':
                    $dateType = \IntlDateFormatter::NONE;
                    $timeType = \IntlDateFormatter::MEDIUM;
                    break;

                default:
                    $pattern = $intlFormats[$format];
            }

            $calendar = \IntlGregorianCalendar::createInstance();
            $calendar->setGregorianChange(PHP_INT_MIN);

            return (new \IntlDateFormatter($locale, $dateType, $timeType, $tz, $calendar, $pattern))->format($timestamp);
        };

        $translationTable = [
            '%a' => $intlFormatter,
            '%A' => $intlFormatter,
            '%d' => 'd',
            '%e' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('j'));
            },
            '%j' => function ($timestamp) {
                return sprintf('%03d', $timestamp->format('z') + 1);
            },
            '%u' => 'N',
            '%w' => 'w',
            '%U' => function ($timestamp) {
                $day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%V' => 'W',
            '%W' => function ($timestamp) {
                $day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%b' => $intlFormatter,
            '%B' => $intlFormatter,
            '%h' => $intlFormatter,
            '%m' => 'm',
            '%C' => function ($timestamp) {
                return floor($timestamp->format('Y') / 100);
            },
            '%g' => function ($timestamp) {
                return substr($timestamp->format('o'), -2);
            },
            '%G' => 'o',
            '%y' => 'y',
            '%Y' => 'Y',
            '%H' => 'H',
            '%k' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('G'));
            },
            '%I' => 'h',
            '%l' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('g'));
            },
            '%M' => 'i',
            '%p' => 'A',
            '%P' => 'a',
            '%r' => 'h:i:s A',
            '%R' => 'H:i',
            '%S' => 's',
            '%T' => 'H:i:s',
            '%X' => $intlFormatter,
            '%z' => 'O',
            '%Z' => 'T',
            '%c' => $intlFormatter,
            '%D' => 'm/d/Y',
            '%F' => 'Y-m-d',
            '%s' => 'U',
            '%x' => $intlFormatter,
        ];

        $out = preg_replace_callback('/(?<!%)%([_#-]?)([a-zA-Z])/', function ($match) use ($translationTable, $timestamp) {
            $prefix = $match[1];
            $char = $match[2];
            $pattern = '%' . $char;

            if ($pattern === '%n') {
                return "\n";
            }

            if ($pattern === '%t') {
                return "\t";
            }

            if (!isset($translationTable[$pattern])) {
                throw new \InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $pattern));
            }

            $replace = $translationTable[$pattern];
            $result = is_string($replace) ? $timestamp->format($replace) : $replace($timestamp, $pattern);

            switch ($prefix) {
                case '_':
                    return preg_replace('/\G0(?=.)/', ' ', $result);

                case '#':
                case '-':
                    return preg_replace('/^0+(?=.)/', '', $result);
            }

            return $result;
        }, $format);

        return str_replace('%%', '%', $out);
    }
}



// @see https://gist.githubusercontent.com/bohwaz/42fc223031e2b2dd2585aab159a20f30/raw/0a47af1f2920a560a89523d75adf9badca156a96/php-8.1-strftime.php

/**
 * Locale-formatted strftime using \IntlDateFormatter (PHP 8.1 compatible)
 * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
 * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
 *
 * Usage:
 * use function \PHP81_BC\strftime;
 * echo strftime('%A %e %B %Y %X', new \DateTime('2021-09-28 00:00:00'), 'fr_FR');
 *
 * Original use:
 * \setlocale('fr_FR.UTF-8', LC_TIME);
 * echo \strftime('%A %e %B %Y %X', strtotime('2021-09-28 00:00:00'));
 *
 * @param  string $format Date format
 * @param  integer|string|DateTime $timestamp Timestamp
 * @return string
 * @author BohwaZ <https://bohwaz.net/>
 */
function dhh_strftime(string $format, $timestamp = null, ?string $locale = null): string {
	if (null === $timestamp) {
		$timestamp = new DateTime;
	}
	elseif (is_numeric($timestamp)) {
		$timestamp = date_create('@' . $timestamp);

		if ($timestamp) {
			$timestamp->setTimezone(new DateTimezone(date_default_timezone_get()));
		}
	}
	elseif (is_string($timestamp)) {
		$timestamp = date_create($timestamp);
	}

	if (!($timestamp instanceof DateTimeInterface)) {
		throw new InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.');
	}

	$locale = substr((string) $locale, 0, 5);

	$intl_formats = [
		'%a' => 'EEE',	// An abbreviated textual representation of the day	Sun through Sat
		'%A' => 'EEEE',	// A full textual representation of the day	Sunday through Saturday
		'%b' => 'MMM',	// Abbreviated month name, based on the locale	Jan through Dec
		'%B' => 'MMMM',	// Full month name, based on the locale	January through December
		'%h' => 'MMM',	// Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
	];

	$intl_formatter = function (DateTimeInterface $timestamp, string $format) use ($intl_formats, $locale) {
		$tz = $timestamp->getTimezone();
		$date_type = IntlDateFormatter::FULL;
		$time_type = IntlDateFormatter::FULL;
		$pattern = '';

		// %c = Preferred date and time stamp based on locale
		// Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
		if ($format == '%c') {
			$date_type = IntlDateFormatter::LONG;
			$time_type = IntlDateFormatter::SHORT;
		}
		// %x = Preferred date representation based on locale, without the time
		// Example: 02/05/09 for February 5, 2009
		elseif ($format == '%x') {
			$date_type = IntlDateFormatter::SHORT;
			$time_type = IntlDateFormatter::NONE;
		}
		// Localized time format
		elseif ($format == '%X') {
			$date_type = IntlDateFormatter::NONE;
			$time_type = IntlDateFormatter::MEDIUM;
		}
		else {
			$pattern = $intl_formats[$format];
		}

		return (new IntlDateFormatter($locale, $date_type, $time_type, $tz, null, $pattern))->format($timestamp);
	};

	// Same order as https://www.php.net/manual/en/function.strftime.php
	$translation_table = [
		// Day
		'%a' => $intl_formatter,
		'%A' => $intl_formatter,
		'%d' => 'd',
		'%e' => function ($timestamp) {
			return sprintf('% 2u', $timestamp->format('j'));
		},
		'%j' => function ($timestamp) {
			// Day number in year, 001 to 366
			return sprintf('%03d', $timestamp->format('z')+1);
		},
		'%u' => 'N',
		'%w' => 'w',

		// Week
		'%U' => function ($timestamp) {
			// Number of weeks between date and first Sunday of year
			$day = new DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
			return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
		},
		'%V' => 'W',
		'%W' => function ($timestamp) {
			// Number of weeks between date and first Monday of year
			$day = new DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
			return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
		},

		// Month
		'%b' => $intl_formatter,
		'%B' => $intl_formatter,
		'%h' => $intl_formatter,
		'%m' => 'm',

		// Year
		'%C' => function ($timestamp) {
			// Century (-1): 19 for 20th century
			return floor($timestamp->format('Y') / 100);
		},
		'%g' => function ($timestamp) {
			return substr((string) $timestamp->format('o'), -2);
		},
		'%G' => 'o',
		'%y' => 'y',
		'%Y' => 'Y',

		// Time
		'%H' => 'H',
		'%k' => function ($timestamp) {
			return sprintf('% 2u', $timestamp->format('G'));
		},
		'%I' => 'h',
		'%l' => function ($timestamp) {
			return sprintf('% 2u', $timestamp->format('g'));
		},
		'%M' => 'i',
		'%p' => 'A', // AM PM (this is reversed on purpose!)
		'%P' => 'a', // am pm
		'%r' => 'h:i:s A', // %I:%M:%S %p
		'%R' => 'H:i', // %H:%M
		'%S' => 's',
		'%T' => 'H:i:s', // %H:%M:%S
		'%X' => $intl_formatter, // Preferred time representation based on locale, without the date

		// Timezone
		'%z' => 'O',
		'%Z' => 'T',

		// Time and Date Stamps
		'%c' => $intl_formatter,
		'%D' => 'm/d/Y',
		'%F' => 'Y-m-d',
		'%s' => 'U',
		'%x' => $intl_formatter,
	];

	$out = preg_replace_callback('/(?<!%)(%[a-zA-Z])/', function ($match) use ($translation_table, $timestamp) {
		if ($match[1] == '%n') {
			return "\n";
		}
		elseif ($match[1] == '%t') {
			return "\t";
		}

		if (!isset($translation_table[$match[1]])) {
			throw new InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $match[1]));
		}

		$replace = $translation_table[$match[1]];

		if (is_string($replace)) {
			return $timestamp->format($replace);
		}
		else {
			return $replace($timestamp, $match[1]);
		}
	}, $format);

	$out = str_replace('%%', '%', (string) $out);
	return $out;
}
