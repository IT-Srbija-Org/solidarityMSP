#!/bin/bash

LOG_TAG="tor-blocker"
TEMP_FILE="/etc/nginx/snippets/tor-ips.conf.tmp"
TARGET_FILE="/etc/nginx/snippets/tor-ips.conf"

# Download and process exit nodes
if wget -qO- https://check.torproject.org/exit-addresses | grep ExitAddress | cut -d ' ' -f 2 | sed "s/^/    /g; s/$/  1;/g" | sort | uniq > "$TEMP_FILE"; then
    # Verify file has content and apply
    if [ -s "$TEMP_FILE" ]; then
        cp -f "$TARGET_FILE" "$TARGET_FILE.bak" 2>/dev/null
        mv "$TEMP_FILE" "$TARGET_FILE"
        IP_COUNT=$(grep -c "1;" "$TARGET_FILE")

        # Reload nginx
        if systemctl reload nginx; then
            logger -t "$LOG_TAG" "Updated: $IP_COUNT Tor exit nodes blocked"
        else
            logger -t "$LOG_TAG" "Error: nginx reload failed, reverting"
            mv -f "$TARGET_FILE.bak" "$TARGET_FILE" 2>/dev/null
            systemctl reload nginx
        fi
    else
        rm -f "$TEMP_FILE"
        logger -t "$LOG_TAG" "Error: Empty file downloaded"
    fi
else
    logger -t "$LOG_TAG" "Error: Download failed"
fi
