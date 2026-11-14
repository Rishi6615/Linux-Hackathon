#!/bin/bash
# Smart Granular Recovery Tool - ONLY SHOWS ACTUALLY DELETED FILES

echo "=============================================================================="
echo "          🏨 GRAND HERITAGE HOTEL - RECOVERY OF DELETED GUESTS                "
echo "=============================================================================="
echo ""
echo "Scanning backup vault for deleted guest bookings missing from disk..."
echo ""

TMP_LIST="/tmp/hotel_deleted_guests.txt"
rm -f "$TMP_LIST"

# Search all archives for booking files and filter ONLY THOSE MISSING FROM DISK
for arc in $(ls -t /backup/*.tar.gz 2>/dev/null); do
    for fpath in $(tar -tzf "$arc" 2>/dev/null | grep -E "srv/hotel/reception/booking_[0-9]+\.txt"); do
        FNAME=$(basename "$fpath")
        # Check if already in list
        if ! grep -q "^$FNAME:" "$TMP_LIST" 2>/dev/null; then
            # ONLY include if the file is ACTUALLY DELETED on disk!
            if [ ! -f "/$fpath" ]; then
                GNAME=$(tar -xzf "$arc" "$fpath" -O 2>/dev/null | grep -i "^GUEST NAME:" | head -1 | cut -d':' -f2 | xargs)
                if [ -z "$GNAME" ]; then
                    GNAME=$(tar -xzf "$arc" "$fpath" -O 2>/dev/null | grep -i "^Guest:" | head -1 | cut -d':' -f2 | xargs)
                fi
                if [ -z "$GNAME" ]; then
                    GNAME="Registered Guest"
                fi
                ROOM=$(echo "$FNAME" | sed 's/booking_//;s/\.txt//')
                echo "$FNAME:$ROOM:$GNAME:$arc:$fpath" >> "$TMP_LIST"
            fi
        fi
    done
done

if [ ! -s "$TMP_LIST" ]; then
    echo "------------------------------------------------------------------------------"
    echo "✓ All guest records from the backup vault are currently ACTIVE on disk."
    echo "  No deleted bookings found to restore!"
    echo "------------------------------------------------------------------------------"
    rm -f "$TMP_LIST"
    exit 0
fi

echo "The following guest records were DELETED and can be restored:"
echo "------------------------------------------------------------------------------"
printf " %-4s | %-26s | %-12s | %s\n" "No." "DELETED GUEST NAME" "ROOM NO." "ACTION"
echo "------------------------------------------------------------------------------"

INDEX=1
declare -a FILES
declare -a ARCS
declare -a PATHS
declare -a NAMES

while IFS=':' read -r fname room gname arc fpath; do
    printf " [%2d] | %-26s | Room %-7s | [Ready to Restore]\n" "$INDEX" "$gname" "$room"
    FILES[$INDEX]="$fname"
    ARCS[$INDEX]="$arc"
    PATHS[$INDEX]="$fpath"
    NAMES[$INDEX]="$gname"
    INDEX=$((INDEX + 1))
done < "$TMP_LIST"

echo "------------------------------------------------------------------------------"
echo ""
echo -n "Enter the NUMBER of the deleted guest to restore (1-$((INDEX - 1))) or Name: "
read -r CHOICE

if [ -z "$CHOICE" ]; then
    echo "No selection made. Exiting."
    rm -f "$TMP_LIST"
    exit 0
fi

SELECTED_FILE=""
SELECTED_ARC=""
SELECTED_PATH=""
SELECTED_NAME=""

# Check if input is a number
if [[ "$CHOICE" =~ ^[0-9]+$ ]] && [ "$CHOICE" -ge 1 ] && [ "$CHOICE" -lt "$INDEX" ]; then
    SELECTED_FILE="${FILES[$CHOICE]}"
    SELECTED_ARC="${ARCS[$CHOICE]}"
    SELECTED_PATH="${PATHS[$CHOICE]}"
    SELECTED_NAME="${NAMES[$CHOICE]}"
else
    # Try searching by guest name
    MATCH_LINE=$(grep -i ":$CHOICE" "$TMP_LIST" | head -1)
    if [ -n "$MATCH_LINE" ]; then
        SELECTED_FILE=$(echo "$MATCH_LINE" | cut -d':' -f1)
        SELECTED_NAME=$(echo "$MATCH_LINE" | cut -d':' -f3)
        SELECTED_ARC=$(echo "$MATCH_LINE" | cut -d':' -f4)
        SELECTED_PATH=$(echo "$MATCH_LINE" | cut -d':' -f5)
    fi
fi

if [ -z "$SELECTED_PATH" ]; then
    echo "❌ Selection not recognized. Please choose from the deleted list above."
    rm -f "$TMP_LIST"
    exit 1
fi

echo ""
echo "--> Restoring deleted record for: $SELECTED_NAME (Room $(echo $SELECTED_FILE | sed 's/booking_//;s/\.txt//'))"
echo "--> Archive Source: $(basename "$SELECTED_ARC")"

tar -xzf "$SELECTED_ARC" -C / "$SELECTED_PATH"

echo ""
echo "=== Restored Record Details ==="
cat "/$SELECTED_PATH"
echo ""
echo "=============================================================================="
echo "✓ SUCCESS: '$SELECTED_NAME' has been restored to active operations!"
echo "=============================================================================="

rm -f "$TMP_LIST"
