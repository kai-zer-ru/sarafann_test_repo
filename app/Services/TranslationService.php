<?php

namespace App\Services;


class TranslationService
{
    public static function clearSymbols($text)
    {
        return preg_replace('~[^- a-zA-Z0-9а-яА-ЯёЁ<>{}[]]+~u', '', $text);
    }

    public static function clearTags($text)
    {
        $text = str_replace('<p>&nbsp;</p>', '', $text);

        return strip_tags(preg_replace('/style="(.*)">/i', '>', $text), '<p><ul><li><br><i>');
    }

    public static function clearTagsFull($text)
    {
        $text = str_replace('<p>&nbsp;</p>', '', $text);

        return strip_tags($text, '<br>');
    }

    public static function clearTagsFullFull($text)
    {
        $search = ["'<script[^>]*?>.*?</script>'si", // Вырезает javaScript
            "'<[\\/\\!]*?[^<>]*?>'si", // Вырезает HTML-теги
            "'([\r\n])[\\s]+'", // Вырезает пробельные символы
            "'&(quot|#34);'i", // Заменяет HTML-сущности
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i", ];

        $replace = ['',
            '',
            '\\1',
            '"',
            '&',
            '<',
            '>',
            ' ',
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            'chr(\\1)', ];

        $text = preg_replace($search, $replace, $text);

        return strip_tags($text);
    }

    public static function str2url($str)
    {
        // переводим в транслит
        $str = self::rus2translit($str);
        // в нижний регистр
        $str = strtolower($str);
        // заменям все ненужное нам на "-"
        $str = preg_replace('~[^-a-z0-9_.]+~u', '-', $str);
        // удаляем начальные и конечные '-'
        return trim($str, '-');
    }

    public static function url2str($str)
    {
        $str = self::eng2rus($str);

        return str_replace('-', ' ', $str);
    }

    public static function eng2rus($string)
    {
        $converter = [
            'a' => 'а',
            'b' => 'б',
            'v' => 'в',
            'g' => 'г',
            'd' => 'д',
            'e' => 'э',
            'zh' => 'ж',
            'z' => 'з',
            'i' => 'и',
            'y' => 'ы',
            'k' => 'к',
            'l' => 'л',
            'm' => 'м',
            'n' => 'н',
            'o' => 'о',
            'p' => 'п',
            'r' => 'р',
            's' => 'с',
            't' => 'т',
            'u' => 'у',
            'f' => 'ф',
            'h' => 'х',
            'c' => 'ц',
            'ch' => 'ч',
            'sh' => 'ш',
            'sch' => 'щ',
            'yu' => 'ю',
            'ya' => 'я',
            'A' => 'А',
            'B' => 'Б',
            'V' => 'В',
            'G' => 'Г',
            'D' => 'Д',
            'E' => 'Э',
            'Zh' => 'Ж',
            'Z' => 'З',
            'I' => 'И',
            'Y' => 'Ы',
            'K' => 'К',
            'L' => 'Л',
            'M' => 'М',
            'N' => 'Н',
            'O' => 'О',
            'P' => 'П',
            'R' => 'Р',
            'S' => 'С',
            'T' => 'Т',
            'U' => 'У',
            'F' => 'Ф',
            'H' => 'Х',
            'C' => 'Ц',
            'Ch' => 'Ч',
            'Sh' => 'Ш',
            'Sch' => 'Щ',
            'Yu' => 'Ю',
            'Ya' => 'Я',
            '-' => '.',
        ];

        return strtr($string, $converter);
    }

    public static function rus2translit($string)
    {
        $converter = [
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
            'и' => 'i', 'й' => 'y', 'к' => 'k',
            'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r',
            'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'A', 'Б' => 'B', 'В' => 'V',
            'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
            'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K',
            'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R',
            'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
            'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            '.' => '-',
        ];

        return strtr($string, $converter);
    }

    public static function translate($query)
    {
        $converter = [
            'а' => 'f',
            'А' => 'F',
            'f' => 'а',
            'F' => 'А',
            'б' => ',',
            ',' => 'б',
            'Б' => '<',
            '<' => 'Б',
            'в' => 'd',
            'В' => 'D',
            'd' => 'в',
            'D' => 'В',
            'г' => 'u',
            'Г' => 'U',
            'u' => 'г',
            'U' => 'Г',
            'д' => 'l',
            'Д' => 'L',
            'l' => 'д',
            'L' => 'Д',
            'е' => 't',
            'Е' => 'T',
            't' => 'е',
            'T' => 'Е',
            'ё' => '`',
            'Ё' => '~',
            '`' => 'ё',
            '~' => 'Ё',
            'ж' => ';',
            'Ж' => ':',
            ':' => 'Ж',
            ';' => 'ж',
            'з' => 'p',
            'З' => 'P',
            'p' => 'з',
            'P' => 'З',
            'и' => 'b',
            'И' => 'B',
            'b' => 'и',
            'B' => 'И',
            'й' => 'q',
            'Й' => 'Q',
            'q' => 'й',
            'Q' => 'Й',
            'к' => 'r',
            'К' => 'R',
            'r' => 'к',
            'R' => 'К',
            'k' => 'л',
            'K' => 'Л',
            'л' => 'k',
            'Л' => 'K',
            'м' => 'v',
            'М' => 'V',
            'v' => 'м',
            'V' => 'М',
            'н' => 'y',
            'Н' => 'Y',
            'y' => 'н',
            'Y' => 'Н',
            'о' => 'j',
            'О' => 'J',
            'j' => 'о',
            'J' => 'О',
            'п' => 'g',
            'П' => 'G',
            'g' => 'п',
            'G' => 'П',
            'р' => 'h',
            'Р' => 'H',
            'h' => 'р',
            'H' => 'Р',
            'с' => 'c',
            'С' => 'C',
            'c' => 'с',
            'C' => 'С',
            'т' => 'n',
            'Т' => 'N',
            'n' => 'т',
            'N' => 'Т',
            'у' => 'e',
            'У' => 'E',
            'e' => 'у',
            'E' => 'У',
            'ф' => 'a',
            'Ф' => 'A',
            'a' => 'ф',
            'A' => 'Ф',
            'х' => '[',
            'Х' => '{',
            '[' => 'х',
            '{' => 'Х',
            'ц' => 'w',
            'Ц' => 'W',
            'w' => 'ц',
            'W' => 'Ц',
            'ч' => 'x',
            'Ч' => 'X',
            'x' => 'ч',
            'X' => 'Ч',
            'ш' => 'i',
            'Ш' => 'I',
            'i' => 'ш',
            'I' => 'Ш',
            'щ' => 'o',
            'Щ' => 'O',
            'o' => 'щ',
            'O' => 'Щ',
            'ь' => 'm',
            'Ь' => 'M',
            'm' => 'ь',
            'M' => 'Ь',
            'ы' => 's',
            'Ы' => 'S',
            's' => 'ы',
            'S' => 'Ы',
            'ъ' => ']',
            'Ъ' => '}',
            '}' => 'Ъ',
            ']' => 'ъ',
            'э' => '"',
            'Э' => "'",
            "'" => 'Э',
            '"' => 'э',
            'ю' => '.',
            'Ю' => '>',
            '>' => 'Ю',
            '.' => 'ю',
            'я' => 'z',
            'Я' => 'Z',
            'z' => 'я',
            'Z' => 'Я', ];

        return strtr($query, $converter);
    }

    public static function getRuDate($date)
    {
        [$all_msg_date, $msg_date, $cur_date, $yesterday_date, $past_yesterday_date] = self::converDates($date);
        if ($cur_date === $msg_date) {
            $cur_minute = date('i');
            $cur_hour = date('H');
            $msg_minute = mb_substr($all_msg_date, 14, 2);
            $msg_hour = mb_substr($all_msg_date, 11, 2);

            if ($msg_hour === $cur_hour) {
                if ($msg_minute === $cur_minute) {
                    $date_ru = 'Только что';
                } else {
                    $diff = (int) $cur_minute - (int) $msg_minute;
                    $date_ru = $diff.' '.self::getSclonText($diff, 'минут назад', 'минуту назад', 'минуты назад');
                }
            } else {
                $diff = (int) $cur_hour - (int) $msg_hour;
                if ($diff <= 3) {
                    $date_ru = $diff.' '.self::getSclonText($diff, 'часов назад', 'час назад', 'часа назад');
                } else {
                    $date_ru = 'Сегодня, в '.mb_substr($all_msg_date, 11, 5);
                }
            }
        } elseif ($yesterday_date === $msg_date) {
            $date_ru = 'Вчера, в '.mb_substr($all_msg_date, 11, 5);
        } elseif ($past_yesterday_date === $msg_date) {
            $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2)).' в '.mb_substr($all_msg_date, 11, 5);
        } else {
            $msg_year = mb_substr($all_msg_date, 0, 4);
            $cur_year = date('Y');
            if ($msg_year === $cur_year) {
                $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2)).' в '.mb_substr($all_msg_date, 11, 5);
            } else {
                $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2)).' '.$msg_year.' года';
            }
        }

        return $date_ru;
    }

    public static function getSclonText($count, $text11, $text1, $text2)
    {
        switch ($count) {
            case 0:
            case 11:
            case 12:
            case 13:
            case 14:
                $count_name = $text11;

                break;
            default:
                switch ($count % 10) {
                    case 1:
                        $count_name = $text1;

                        break;
                    case 2:
                    case 3:
                    case 4:
                        $count_name = $text2;

                        break;
                    default:
                        $count_name = $text11;

                        break;
                }
        }

        return $count_name;
    }

    public static function GetRuMonth($MonthNum)
    {
        switch ($MonthNum) {
            case '01':
                return 'января';
            case '02':
                return 'февраля';
            case '03':
                return 'марта';
            case '04':
                return 'апреля';
            case '05':
                return 'мая';
            case '06':
                return 'июня';
            case '07':
                return 'июля';
            case '08':
                return 'августа';
            case '09':
                return 'сентября';
            case '10':
                return 'октября';
            case '11':
                return 'ноября';
            case '12':
                return 'декабря';
        }

        return '';
    }

    public static function getRuDateWithoutTime($date)
    {
        [$all_msg_date, $msg_date, $cur_date, $yesterday_date, $past_yesterday_date] = self::converDates($date);
        if ($cur_date === $msg_date) {
            $date_ru = 'Сегодня';
        } elseif ($yesterday_date === $msg_date) {
            $date_ru = 'Вчера';
        } elseif ($past_yesterday_date === $msg_date) {
            $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2));
        } else {
            $msg_year = mb_substr($all_msg_date, 0, 4);
            $cur_year = date('Y');
            if ($msg_year === $cur_year) {
                $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2));
            } else {
                $date_ru = self::getDayOfMonth($msg_date).' '.self::GetRuMonth(substr($msg_date, 5, 2)).' '.$msg_year.' года';
            }
        }

        return $date_ru;
    }

    public static function onlyEngAndNum($text)
    {
        return preg_replace('~[^-_a-zA-Z0-9]+~u', '', $text);
    }

    private static function converDates($date)
    {
        if (!$date) {
            return null;
        }
        $all_msg_date = $date;
        $msg_date = mb_substr($all_msg_date, 0, 10);
        $cur_date = date('Y-m-d');
        $yesterday_date = date('Y-m-d', time() - (24 * 60 * 60));
        $past_yesterday_date = date('Y-m-d', time() - (2 * 24 * 60 * 60));

        return [$all_msg_date, $msg_date, $cur_date, $yesterday_date, $past_yesterday_date];
    }

    private static function getDayOfMonth($msg_date)
    {
        $day = mb_substr($msg_date, 8, 2);
        if (0 === strpos($day, '0')) {
            return $day[1];
        }

        return $day;
    }
}
