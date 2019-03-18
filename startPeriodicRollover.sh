#!/bin/bash

now="$(date +'%Y%m%d')"
nowb="$(date +'%Y/%m/%d')"
f=ro$now.log
fsep="=========================================================================================="

printf "==================================================\n"
printf "Starting Periodic Rollover\n\n"
printf "Current date is %s\n\n" "$now"

tasktitle="Updating agents links"
printf "$tasktitle... "
echo "$fsep" >> $f
echo "$tasktitle" >> $f
echo >> $f
php index.php pamm update_agents_links >> $f
echo >> $f
echo >> $f
printf "done\n"

tasktitle="Updating investors stat cache"
printf "$tasktitle... "
echo "$fsep" >> $f
echo "$tasktitle" >> $f
echo >> $f
php index.php pamm update_investors_stat_cache 1 >> $f
echo >> $f
echo >> $f
printf "done\n"

tasktitle="Running pre rollover queries"
printf "$tasktitle... "
echo "$fsep" >> $f
echo "$tasktitle" >> $f
echo >> $f
php index.php pamm run_pre_rollover >> $f
echo >> $f
echo >> $f
printf "done\n"

tasktitle="Running rollover"
printf "$tasktitle... "
echo "$fsep" >> $f
echo "$tasktitle" >> $f
echo >> $f
php index.php pamm run_periodic_rollover 1 >> $f
echo >> $f
echo >> $f
printf "done\n"

printf "Rollover on $nowb completed.\n\nRollover detailed log is in attachment.\n\nNow you can open http://api.privatefx.com/pamm/run_periodic_rollover/ and close all PAMMs that wasn't allowed to rollover.\nHere is a list of them:\n" > muttbody
php index.php pamm run_periodic_rollover >> muttbody
tar czf ro$now.log.tar.gz ro$now.log
mutt -a ro$now.log.tar.gz -e 'my_hdr From: Rollover Logger <noreply@privatefx.com>' -s "Rollover on $nowb" -- minister87@gmail.com artemkhodos@gmail.com bigferumdron@gmail.com nickotin.zp.ua@gmail.com muravshchyk@gmail.com o.prog123456@gmail.com < muttbody
rm muttbody
rm ro$now.log
#rm ro$now.log.tar.gz

printf "\nSCRIPT ENDED\n\n"
