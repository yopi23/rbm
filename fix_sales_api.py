import re

file_path = 'C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Api/SalesApiController.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Add is_active filter in search function
pattern = re.compile(r'(\$query->where\(\'kode_owner\', \$userId\);)')
if pattern.search(content):
    content = pattern.sub(r"\1\n            $query->where('is_active', true);", content)
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added is_active filter to SalesApiController@search")
else:
    print("Could not find the pattern.")
