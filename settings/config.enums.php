<?php

/* Example:
abstract class TimeframeType extends Enum {
 const Continuous=1;
 const Daily=2;
 const Weekly=3;
 const Monthly=4;
 const Quarterly=5;
 const Yearly=6;
 static function name($n) {
  switch(intval($n)) {
   case TimeframeType::Continuous: return 'Continuous';
   case TimeframeType::Daily: return 'Daily';
   case TimeframeType::Weekly: return 'Weekly';
   case TimeframeType::Monthly: return 'Monthly';
   case TimeframeType::Quarterly: return 'Quarterly';
   case TimeframeType::Yearly: return 'Yearly';
   default: return 'Timeless'; break;
  }
 }
};
*/
