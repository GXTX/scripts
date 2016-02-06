let upSeconds="$(/usr/bin/cut -d. -f1 /proc/uptime)"
let secs=$((${upSeconds}%60))
let mins=$((${upSeconds}/60%60))
let hours=$((${upSeconds}/3600%24))
let days=$((${upSeconds}/86400))
UPTIME=`printf "%d days, %02d:%02d:%02d" "$days" "$hours" "$mins" "$secs"`

echo "
   .~~.   .~~.    `date +"%A, %e %B %Y, %r"`
  '. \ ' ' / .'   `uname -srmo`
   .~ .~~~..~.
  : .~.'~'.~. :   Uptime.............: ${UPTIME}
 ~ (   ) (   ) ~  Memory.............: `cat /proc/meminfo | grep MemFree | awk {'print $2'}`kB (Free) / `cat /proc/meminfo | grep MemTotal | awk {'print $2'}`kB (Total)
( : '~'.~.'~' : ) Load Averages......: $(uptime | grep -Eo 'load.*' | awk '{ print $3,$4,$5 }') (1, 5, 15 min)
 ~ .~ (   ) ~. ~  Running Processes..: `ps | wc -l | tr -d " "`
  (  : '~' :  )   Public IP Address..: `wget -q -O - http://icanhazip.com/ | tail`
   '~ .~~~. ~'    Temperatures.......: CPU: $((`cat /proc/dmu/temperature|awk {' print $1 '}`/10)) | 2.4GHz: $((`wl -i eth1 phy_tempsense|awk {' print $1 '}`/2+20))C / 5GHz: $((`wl -i eth2 phy_tempsense|awk {' print $1 '}`/2+20))C
       '~'
"
