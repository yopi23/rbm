import re

files = [
    'C:/Users/yopi/Documents/project/rbm/docker-compose.yml.lokal',
    'C:/Users/yopi/Documents/project/rbm/docker-compose.vps.yml',
    'C:/Users/yopi/Documents/project/rbm/docker-compose.stb.yml'
]

redis_block = """
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
            if 'app-network' in content:
                rb = redis_block_vps
            else:
                rb = redis_block

            # insert right after services:\n
            content = re.sub(r'(services:\s*\n)', r'\1' + rb, content, count=1)
            with open(file, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Added redis to {file}")
    except Exception as e:
        print(f"Failed to process {file}: {e}")
