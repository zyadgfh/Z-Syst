from pathlib import Path
p = Path('Modules/Landing/Database/Seeders/OptionTableSeeder.php')
text = p.read_text(encoding='utf-8')
replacements = {
    'https://www.instagram.com/acnooteam/': 'https://www.instagram.com/zsyst/',
    'https://www.facebook.com/acnooteam': 'https://www.facebook.com/zsyst',
    'QUIZYS.IN': 'Z-Syst',
    'Quizys': 'Z-Syst',
}
for old, new in replacements.items():
    text = text.replace(old, new)
p.write_text(text, encoding='utf-8')
print('updated')
