import re

files = [
    'C:/Users/yopi/Documents/project/rbm/docker-compose.yml.lokal',
    'C:/Users/yopi/Documents/project/rbm/docker-compose.vps.yml',
    'C:/Users/yopi/Documents/project/rbm/docker-compose.stb.yml'
]

redis_block = """
  #Redis Service
  redis:
    image: redis:alpine
    container_name: rbm_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - rbmnet
"""
redis_block_vps = redis_block.replace('rbmnet', 'app-network')

for file in files:
    try:
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if 'redis:' not in content:
            # Find the networks block to insert before it
            if 'networks:' in content:
                # determine network name
                if 'app-network' in content:
                    rb = redis_block_vps
                else:
                    rb = redis_block

                # insert right before networks: (make sure it's at the root level)
                content = re.sub(r'(\n#?.*networks:)', rb + r'\1', content, count=1)
                with open(file, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Added redis to {file}")
            else:
                print(f"Could not find networks block in {file}")
        else:
            print(f"Redis already exists in {file}")
    except Exception as e:
        print(f"Failed to process {file}: {e}")

# Update .env files
env_files = [
    'C:/Users/yopi/Documents/project/rbm/.env',
    'C:/Users/yopi/Documents/project/rbm/.env.docker'
]

for file in env_files:
    try:
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        content = re.sub(r'CACHE_DRIVER=.*', 'CACHE_DRIVER=redis', content)
        content = re.sub(r'REDIS_HOST=.*', 'REDIS_HOST=redis', content)
        
        if 'REDIS_CLIENT=' not in content:
            content += '\nREDIS_CLIENT=predis\n'
        else:
            content = re.sub(r'REDIS_CLIENT=.*', 'REDIS_CLIENT=predis', content)
            
        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {file}")
    except Exception as e:
        print(f"Failed to update {file}: {e}")

