# Documented firewall rules; run individually only on the intended project VM.
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp comment 'OpenSSH'
sudo ufw allow 80/tcp comment 'Apache HTTP'
sudo ufw --force enable
