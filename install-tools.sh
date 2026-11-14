#!/bin/bash
# Clean, Real-Command Interactive Demo Tools for Grand Heritage Hotel

# Tool 1: show-storage
cat << 'EOF' > /usr/local/bin/show-storage
#!/bin/bash
clear
echo "=== 1. Physical Disks (pvscan) ==="
pvscan
echo ""
echo "=== 2. Storage Pool (vgdisplay vg_hotel) ==="
vgs vg_hotel
echo ""
echo "=== 3. Department Partitions (lvscan) ==="
lvscan
echo ""
echo "=== 4. Active Mounted Drives (df -h) ==="
df -h /srv/hotel/reception /srv/hotel/housekeeping /srv/hotel/admin /backup
EOF
chmod +x /usr/local/bin/show-storage

# Tool 2: show-backup
cat << 'EOF' > /usr/local/bin/show-backup
#!/bin/bash
clear
echo "=== Backup Vault Location: /backup/ ==="
ls -lh /backup/*.tar.gz
echo ""
echo "=== Files Stored Inside the Archive (tar -tf) ==="
LATEST=$(ls -t /backup/*.tar.gz | head -1)
tar -tzf "$LATEST" | head -15
EOF
chmod +x /usr/local/bin/show-backup

# Tool 3: delete-file
cat << 'EOF' > /usr/local/bin/delete-file
#!/bin/bash
FILE="/srv/hotel/reception/booking_101.txt"
if [ ! -f "$FILE" ]; then
    echo "Room: 101, Guest: Eleanor Vance, Deluxe King" > "$FILE"
    chown rec1:reception "$FILE"
fi
echo "Current file on disk:"
ls -l "$FILE"
cat "$FILE"
echo ""
echo "--> Deleting $FILE now..."
rm -f "$FILE"
echo ""
echo "Checking file again:"
ls -l "$FILE"
EOF
chmod +x /usr/local/bin/delete-file

# Tool 4: restore-file
cat << 'EOF' > /usr/local/bin/restore-file
#!/bin/bash
LATEST=$(ls -t /backup/*.tar.gz | head -1)
echo "Restoring from archive: $LATEST"
tar -xzf "$LATEST" -C / srv/hotel/reception/booking_101.txt
echo ""
echo "Checking restored file on disk:"
ls -l /srv/hotel/reception/booking_101.txt
cat /srv/hotel/reception/booking_101.txt
echo ""
echo "Restoration Successful!"
EOF
chmod +x /usr/local/bin/restore-file

echo "All 4 commands installed successfully."
