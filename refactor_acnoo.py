import os
import re
import glob

base_dir = r"d:\Zyad\laragon\www"

# 1. Rename files and update class names within them
dirs_to_check = [
    os.path.join(base_dir, 'app', 'Http', 'Controllers'),
    os.path.join(base_dir, 'Modules', 'Landing', 'App', 'Http', 'Controllers'),
]

renamed_classes = {}

for d in dirs_to_check:
    for root, dirs, files in os.walk(d):
        for f in files:
            if 'Acnoo' in f and f.endswith('.php'):
                old_path = os.path.join(root, f)
                new_f = f.replace('Acnoo', 'ZSyst')
                new_path = os.path.join(root, new_f)
                
                # Store for reference
                old_class = f.replace('.php', '')
                new_class = new_f.replace('.php', '')
                renamed_classes[old_class] = new_class
                
                # Read content
                with open(old_path, 'r', encoding='utf-8') as file:
                    content = file.read()
                
                # Replace class name
                content = content.replace(f"class {old_class}", f"class {new_class}")
                
                # Replace acnooFilter with zsystFilter
                content = content.replace('acnooFilter', 'zsystFilter')
                
                # Write to new file
                with open(new_path, 'w', encoding='utf-8') as file:
                    file.write(content)
                
                # Remove old file
                os.remove(old_path)
                print(f"Renamed {old_class} to {new_class}")

# 2. Update routes and other controllers that reference these classes
files_to_update = [
    os.path.join(base_dir, 'routes', 'admin.php'),
    os.path.join(base_dir, 'routes', 'api.php'),
    os.path.join(base_dir, 'Modules', 'Landing', 'routes', 'admin.php'),
]

# Add all controllers to update internal references
for d in dirs_to_check:
    for root, dirs, files in os.walk(d):
        for f in files:
            if f.endswith('.php'):
                files_to_update.append(os.path.join(root, f))

for path in set(files_to_update):
    if os.path.exists(path):
        with open(path, 'r', encoding='utf-8') as file:
            content = file.read()
        
        orig_content = content
        for old_class, new_class in renamed_classes.items():
            content = content.replace(old_class, new_class)
        
        content = content.replace('acnooFilter', 'zsystFilter')
        
        if content != orig_content:
            with open(path, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f"Updated references in {os.path.basename(path)}")

# 3. Update lang/en.json
lang_path = os.path.join(base_dir, 'lang', 'en.json')
if os.path.exists(lang_path):
    with open(lang_path, 'r', encoding='utf-8') as file:
        content = file.read()
    
    orig_content = content
    content = content.replace('Acnoo Pharmacy Installer', 'Z-Syst Pharmacy Installer')
    content = content.replace('Acnoo', 'Z-Syst') # Any remaining Acnoo
    
    if content != orig_content:
        with open(lang_path, 'w', encoding='utf-8') as file:
            file.write(content)
        print("Updated lang/en.json")
