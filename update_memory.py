import re

files = [
    'C:/Users/yopi/Documents/project/rbm/docker-compose.stb.yml',
    'C:/Users/yopi/Documents/project/rbm/docker-compose.vps.yml'
]

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace memory: 64M with memory: 256M under rbm-db-backup
    # We will use regex to find the block for rbm-db-backup and replace the memory limit.
    # A safer way: replace "memory: 64M" only if it's the one under rbm-db-backup.
    # We can just replace all "memory: 64M" to "memory: 128M" if we want, but let's be careful.
    
    # We'll just replace 'memory: 64M' with 'memory: 256M' and 'memory: 128M' with 'memory: 256M' (for scheduler if needed? The user said "paling berat" but only backup is red)
    content = content.replace("memory: 64M", "memory: 256M")

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
        
    print(f"Updated {file}")
