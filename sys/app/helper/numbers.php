<?
/**
 * Number related view helpers.
 */

/**
 * Returns the english ordinal suffix.
 * 
 * @param $number
 * @param $start_tag
 * @param $end_tag
 * @return string
 */
function ordinal($number, $start_tag="<sup>", $end_tag="</sup>")
{
    // when fed a number, adds the English ordinal suffix. Works for any
    // number, even negatives

    if ($number % 100 > 10 && $number %100 < 14):
        $suffix = "th";
    else:
        switch($number % 10) {

            case 0:
                $suffix = "th";
                break;

            case 1:
                $suffix = "st";
                break;

            case 2:
                $suffix = "nd";
                break;

            case 3:
                $suffix = "rd";
                break;

            default:
                $suffix = "th";
                break;
        }

    endif;

    return $number.$start_tag.$suffix.$end_tag;
}