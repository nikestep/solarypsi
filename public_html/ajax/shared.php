<?php
// Declare a function to return the tick display value for a date
function getLineTickVal ($date, $mobile) {
    if (substr ($date, -2) === '01' && substr ($date, 5, 2) !== '01') {
        return '|';
    }
    else if (substr ($date, -2) === '15') {
        $month = intval (substr ($date, 5, 2));
        $month_str = '';
        switch ($month) {
            case 1:
                $month_str = $mobile ? 'J' : 'Jan';
                break;
            case 2:
                $month_str = $mobile ? 'F' : 'Feb';
                break;
            case 3:
                $month_str = $mobile ? 'M' : 'Mar';
                break;
            case 4:
                $month_str = $mobile ? 'A' : 'Apr';
                break;
            case 5:
                $month_str = $mobile ? 'M' : 'May';
                break;
            case 6:
                $month_str = $mobile ? 'J' : 'Jun';
                break;
            case 7:
                $month_str = $mobile ? 'J' : 'Jul';
                break;
            case 8:
                $month_str = $mobile ? 'A' : 'Aug';
                break;
            case 9:
                $month_str = $mobile ? 'S' : 'Sep';
                break;
            case 10:
                $month_str = $mobile ? 'O' : 'Oct';
                break;
            case 11:
                $month_str = $mobile ? 'N' : 'Nov';
                break;
            case 12:
                $month_str = $mobile ? 'D' : 'Dec';
                break;
            default:
                break;
        }
        return $month_str;
    }
    else {
        return '';
    }
}

function getBarTickVal ($month, $mobile) {
    $month_str = '';
        switch ($month) {
        case 1:
            $month_str = $mobile ? 'J' : 'Jan';
            break;
        case 2:
            $month_str = $mobile ? 'F' : 'Feb';
            break;
        case 3:
            $month_str = $mobile ? 'M' : 'Mar';
            break;
        case 4:
            $month_str = $mobile ? 'A' : 'Apr';
            break;
        case 5:
            $month_str = $mobile ? 'M' : 'May';
            break;
        case 6:
            $month_str = $mobile ? 'J' : 'Jun';
            break;
        case 7:
            $month_str = $mobile ? 'J' : 'Jul';
            break;
        case 8:
            $month_str = $mobile ? 'A' : 'Aug';
            break;
        case 9:
            $month_str = $mobile ? 'S' : 'Sep';
            break;
        case 10:
            $month_str = $mobile ? 'O' : 'Oct';
            break;
        case 11:
            $month_str = $mobile ? 'N' : 'Nov';
            break;
        case 12:
            $month_str = $mobile ? 'D' : 'Dec';
            break;
        default:
            break;
    }
    return $month_str;
}
?>