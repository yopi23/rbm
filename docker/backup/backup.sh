#!/bin/sh

# Pastikan timezone sudah benar
DATE=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="rbm_db_backup_${DATE}.sql.gz"
REMOTE_PATH="googledrive:RbmBackups"

echo "[$(date)] Memulai proses backup RBM..."

# Dump database dan compress
mariadb-dump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" --skip-ssl "$DB_NAME" | gzip > "/tmp/$FILENAME"

if [ $? -eq 0 ]; then
  echo "[$(date)] Berhasil dump database ke /tmp/$FILENAME"
  
  # Upload ke Google Drive via rclone
  rclone copy "/tmp/$FILENAME" "$REMOTE_PATH"
  
  if [ $? -eq 0 ]; then
    echo "[$(date)] Berhasil upload ke Google Drive ($REMOTE_PATH)"
  else
    echo "[$(date)] GAGAL upload ke Google Drive!"
  fi
  
  # Hapus file lokal di dalam container
  rm -f "/tmp/$FILENAME"
else
  echo "[$(date)] GAGAL melakukan dump database!"
fi

# Opsional: Bersihkan backup lama di GDrive (lebih dari 7 hari)
# Hapus tanda pagar di bawah jika ingin mengaktifkannya
rclone delete --min-age 7d "$REMOTE_PATH"
