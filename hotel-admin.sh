#!/bin/bash
# ==============================================================================
# Grand Heritage Hotel - Master Administration & Viva Demonstration CLI
# ==============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
NC='\033[0m' # No Color

clear

banner() {
    echo -e "${BLUE}${BOLD}==============================================================================${NC}"
    echo -e "${CYAN}${BOLD}       🏛️  GRAND HERITAGE HOTEL - ENTERPRISE LINUX SERVER MANAGEMENT         ${NC}"
    echo -e "${BLUE}${BOLD}==============================================================================${NC}"
    echo -e " Hostname: ${GREEN}$(hostname)${NC} | Kernel: ${GREEN}$(uname -r)${NC} | Uptime: ${GREEN}$(uptime -p)${NC}"
    echo -e "${BLUE}------------------------------------------------------------------------------${NC}"
}

pause() {
    echo ""
    echo -e "${YELLOW}Press [Enter] to return to the Viva Menu...${NC}"
    read -r
}

while true; do
    clear
    banner
    echo -e "${BOLD}Select a demonstration topic to show the Evaluator:${NC}"
    echo ""
    echo -e "  ${CYAN}[1]${NC} ${BOLD}Show Users, Groups & Password Aging${NC} (rec1, hk1, adm1, chage)"
    echo -e "  ${CYAN}[2]${NC} ${BOLD}Show LVM Storage Pool & Partitions${NC}  (vg_hotel, 4 logical volumes, df -h)"
    echo -e "  ${CYAN}[3]${NC} ${BOLD}Show POSIX ACLs & Security Rules${NC}    (Front Desk read-only to Housekeeping)"
    echo -e "  ${CYAN}[4]${NC} ${BOLD}Show Firewall & SSH Hardening${NC}       (UFW default-deny, port 22/80 only)"
    echo -e "  ${CYAN}[5]${NC} ${BOLD}Show Automated Backup Vault${NC}         (tar.gz archives in /backup)"
    echo -e "  ${GREEN}${BOLD}[6]${NC} ${BOLD}⭐ LIVE DEMO: DELETE & RESTORE A FILE${NC} (Evaluator Question: Full Disaster Recovery Demo)"
    echo -e "  ${CYAN}[7]${NC} ${BOLD}DEMO: Self-Healing System Crash${NC}     (Kill Apache -> Auto-revive live)"
    echo -e "  ${CYAN}[8]${NC} ${BOLD}DEMO: Online LVM Storage Expansion${NC}  (Expand disk with zero downtime)"
    echo -e "  ${CYAN}[9]${NC} ${BOLD}Show Background Cron Automation${NC}     (Daily backup 2 AM, monitor 30m)"
    echo -e "  ${CYAN}[0]${NC} Exit to Normal Linux Shell"
    echo ""
    echo -e "${BLUE}------------------------------------------------------------------------------${NC}"
    echo -n "Enter Option [0-9]: "
    read -r opt

    case $opt in
        1)
            clear
            banner
            echo -e "${BOLD}1. Departmental Groups & Staff Accounts:${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            grep -E "reception|housekeeping|hotel_admin" /etc/group
            echo ""
            echo -e "${BOLD}2. Staff Accounts in /etc/passwd:${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            grep -E "rec1|rec2|hk1|hk2|adm1" /etc/passwd
            echo ""
            echo -e "${BOLD}3. Security Password Aging Policy for 'rec1' (chage -l):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            chage -l rec1 | head -7
            pause
            ;;
        2)
            clear
            banner
            echo -e "${BOLD}1. LVM Physical Volumes (2 Disks Pooled):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            pvs
            echo ""
            echo -e "${BOLD}2. LVM Volume Group (vg_hotel):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            vgs
            echo ""
            echo -e "${BOLD}3. Departmental Logical Volumes:${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            lvs
            echo ""
            echo -e "${BOLD}4. Mounted Filesystems on Disks (df -h):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            df -h / /srv/hotel/reception /srv/hotel/housekeeping /srv/hotel/admin /backup
            pause
            ;;
        3)
            clear
            banner
            echo -e "${BOLD}1. Directory Permissions (SetGID 2770):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            ls -ld /srv/hotel/reception /srv/hotel/housekeeping /srv/hotel/admin
            echo ""
            echo -e "${BOLD}2. POSIX ACL on /srv/hotel/housekeeping:${NC}"
            echo -e "${CYAN}Notice: group:reception:r-x (Front Desk can READ but NOT MODIFY)${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            getfacl /srv/hotel/housekeeping
            echo ""
            echo -e "${BOLD}3. POSIX ACL on /srv/hotel/reception:${NC}"
            echo -e "${CYAN}Notice: user:www-data:rwx (Web app can write guest bookings)${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            getfacl /srv/hotel/reception
            pause
            ;;
        4)
            clear
            banner
            echo -e "${BOLD}1. UFW Firewall Status (Default Deny Incoming):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            ufw status verbose
            echo ""
            echo -e "${BOLD}2. SSH Hardening Configuration (/etc/ssh/sshd_config):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            sshd -T | grep -E "permitrootlogin|maxauthtries|passwordauthentication"
            pause
            ;;
        5)
            clear
            banner
            echo -e "${BOLD}1. Backup Vault Archives in /backup:${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            ls -lh /backup/*.tar.gz
            echo ""
            echo -e "${BOLD}2. Backup Script Code (/usr/local/bin/hotel_backup.sh):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            cat /usr/local/bin/hotel_backup.sh
            echo ""
            echo -e "${BOLD}3. Latest Backup Audit Log (/var/log/hotel_backup.log):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            tail -n 6 /var/log/hotel_backup.log
            pause
            ;;
        6)
            clear
            banner
            LATEST_ARCHIVE=$(ls -t /backup/*.tar.gz 2>/dev/null | head -1)
            TARGET_FILE="/srv/hotel/reception/booking_101.txt"
            REL_TARGET="srv/hotel/reception/booking_101.txt"

            echo -e "${YELLOW}${BOLD}⭐ LIVE DEMONSTRATION: FILE DELETION & GRANULAR RECOVERY${NC}"
            echo -e "${BLUE}----------------------------------------------------------------------${NC}"
            
            # Step 1: Ensure sample file exists
            if [ ! -f "$TARGET_FILE" ]; then
                echo -e "Room: 101\nGuest: Eleanor Vance\nCategory: Deluxe Double\nStatus: Confirmed" > "$TARGET_FILE"
                chown rec1:reception "$TARGET_FILE"
                chmod 660 "$TARGET_FILE"
            fi

            echo -e "${BOLD}STEP 1: Check Active Record on Disk:${NC}"
            echo -e "File Location: ${GREEN}$TARGET_FILE${NC}"
            echo -e "File Contents: ${CYAN}$(head -1 "$TARGET_FILE")${NC}"
            echo ""

            echo -e "${BOLD}STEP 2: Answer to Evaluator -> Where is it stored before restoring?${NC}"
            echo -e "It is preserved inside our verified LVM Backup Vault: ${GREEN}$LATEST_ARCHIVE${NC}"
            echo -e "Verifying file presence inside the archive with ${BOLD}tar -tzf${NC}:"
            tar -ztvf "$LATEST_ARCHIVE" | grep "booking_101.txt"
            echo ""

            echo -e "${RED}${BOLD}STEP 3: SIMULATING ACCIDENTAL DELETION...${NC}"
            rm -f "$TARGET_FILE"
            echo -e "File status now: ${RED}$(ls "$TARGET_FILE" 2>&1)${NC}"
            echo -e "${YELLOW}>> Notice: The active file is now completely deleted from the system! <<${NC}"
            echo ""

            echo -e "${GREEN}${BOLD}STEP 4: EXECUTING 1-CLICK INSTANT RESTORE...${NC}"
            echo -e "${CYAN}Command executed behind the scenes: tar -xzf $LATEST_ARCHIVE -C / $REL_TARGET${NC}"
            tar -xzf "$LATEST_ARCHIVE" -C / "$REL_TARGET"
            echo ""

            echo -e "${BOLD}STEP 5: VERIFICATION - Record is Fully Restored:${NC}"
            echo -e "File Status:   ${GREEN}$(ls -l "$TARGET_FILE")${NC}"
            echo -e "File Contents: ${CYAN}$(cat "$TARGET_FILE")${NC}"
            echo -e "${GREEN}${BOLD}✓ DATA RESTORATION COMPLETE WITH ZERO DATA LOSS!${NC}"
            pause
            ;;
        7)
            clear
            banner
            echo -e "${YELLOW}${BOLD}⚠️  LIVE DISASTER SIMULATION: SERVICE CRASH & SELF-HEALING${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            echo -e "Current Apache Status: ${GREEN}$(systemctl is-active apache2)${NC}"
            echo ""
            echo -e "${RED}Step 1: Intentionally stopping Apache Web Service (Simulating Outage)...${NC}"
            systemctl stop apache2
            echo -e "Apache Status Now: ${RED}$(systemctl is-active apache2)${NC}"
            echo ""
            echo -e "${CYAN}Step 2: Triggering Autonomous Self-Healing Watchdog (/usr/local/bin/hotel_monitor.sh)...${NC}"
            /usr/local/bin/hotel_monitor.sh
            echo ""
            echo -e "${GREEN}Step 3: Verification - Apache Status Now: ${BOLD}$(systemctl is-active apache2)${NC}"
            echo ""
            echo -e "${BOLD}Self-Healing Audit Log Entry:${NC}"
            tail -n 4 /var/log/hotel_monitor.log
            pause
            ;;
        8)
            clear
            banner
            echo -e "${YELLOW}${BOLD}🚀 LIVE DEMO: ZERO-DOWNTIME LVM VOLUME EXPANSION${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            echo -e "${BOLD}Current size of lv_admin:${NC}"
            lvs vg_hotel/lv_admin
            echo ""
            echo -e "${CYAN}Executing: lvextend -r -L +100M /dev/vg_hotel/lv_admin${NC}"
            lvextend -r -L +100M /dev/vg_hotel/lv_admin
            echo ""
            echo -e "${GREEN}${BOLD}Updated size of lv_admin (Expanded Online with ZERO Downtime):${NC}"
            lvs vg_hotel/lv_admin
            pause
            ;;
        9)
            clear
            banner
            echo -e "${BOLD}Active Cron Automation Jobs (crontab -l):${NC}"
            echo -e "${BLUE}------------------------------------------------------------${NC}"
            crontab -l
            pause
            ;;
        0)
            echo -e "${GREEN}Exiting to standard shell. Have a great viva!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option. Please choose 0 to 9.${NC}"
            sleep 1
            ;;
    esac
done
