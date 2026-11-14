# Hotel Reservation Centre — Project Code

This folder organizes the implementation files recovered from the `Linux-Hackathon` project directory and its server documentation.

## Folder map

- `website/index.php` — actual PHP application source copied from the project folder.
- `scripts/hotel_backup.sh` — backup script copied verbatim from the project server documentation.
- `scripts/hotel_monitor.sh` — monitoring and service-recovery script copied verbatim from the project server documentation.
- `config/00-hardening.conf` — documented SSH hardening values.
- `config/root-crontab.txt` — documented backup and monitoring schedules.
- `config/ufw-setup.sh` — documented firewall commands.
- `config/fstab-entries-NOT-INCLUDED.txt` — explains why no guessed mount table was added.
- `commands/demo-commands.sh` — read-only/demo commands shown in the project materials.
- `documentation/` — source server notes, viva guide and existing report.

## Important accuracy note

The two shell scripts are the exact script listings in `HOTEL_SERVER_DOCUMENTATION.md`. They were not recovered directly from the running VM, so this bundle does not claim to be a byte-for-byte export of the installed server files. The guide documents the `/etc/fstab` mounts but omits the exact lines and UUIDs; see the note under `config/`. No passwords, private keys, or generated credentials are included.

## Server paths and schedules documented by the project

- Backup script: `/usr/local/bin/hotel_backup.sh` — daily at 02:00.
- Monitor script: `/usr/local/bin/hotel_monitor.sh` — every 30 minutes.
- Website document root: `/var/www/html/` (website source is organized here as `website/index.php`).
- Departments: `/srv/hotel/reception`, `/srv/hotel/housekeeping`, `/srv/hotel/admin`.
- Backup storage: `/backup`.

Read the documentation before running any setup or demo commands. Files under `commands/` are for reference and demonstration; they do not provision or modify a server.
