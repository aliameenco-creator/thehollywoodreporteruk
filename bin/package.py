#!/usr/bin/env python3
"""
Package WordPress Theme and Plugins with POSIX/Unix directory & file attributes.
Prevents the Windows Compress-Archive zero-permission bug on Linux web hosts (Hostinger/LiteSpeed/Nginx).
"""

import os
import stat
import zipfile

PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

PACKAGES = [
    ('wp-content/themes/thr-theme', 'thr-theme.zip', 'thr-theme'),
    ('wp-content/plugins/thr-core', 'thr-core.zip', 'thr-core'),
    ('wp-content/plugins/thr-importer', 'thr-importer.zip', 'thr-importer'),
]

def make_clean_zip(source_rel, output_name, root_name):
    source_dir = os.path.join(PROJECT_ROOT, source_rel)
    output_path = os.path.join(PROJECT_ROOT, output_name)
    
    if os.path.exists(output_path):
        os.remove(output_path)

    with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as z:
        # 1. Add top-level folder entry with 0755
        root_info = zipfile.ZipInfo(f'{root_name}/')
        root_info.create_system = 3 # Unix
        root_info.external_attr = (stat.S_IFDIR | 0o755) << 16
        z.writestr(root_info, '')

        # 2. Walk directory
        for root, dirs, files in os.walk(source_dir):
            for d in sorted(dirs):
                rel_d = os.path.relpath(os.path.join(root, d), source_dir).replace('\\', '/')
                d_info = zipfile.ZipInfo(f'{root_name}/{rel_d}/')
                d_info.create_system = 3 # Unix
                d_info.external_attr = (stat.S_IFDIR | 0o755) << 16
                z.writestr(d_info, '')

            for f in sorted(files):
                abs_f = os.path.join(root, f)
                rel_f = os.path.relpath(abs_f, source_dir).replace('\\', '/')
                with open(abs_f, 'rb') as fp:
                    content = fp.read()
                f_info = zipfile.ZipInfo(f'{root_name}/{rel_f}')
                f_info.create_system = 3 # Unix
                f_info.external_attr = (stat.S_IFREG | 0o644) << 16
                z.writestr(f_info, content, compress_type=zipfile.ZIP_DEFLATED)

    print(f'[+] Packaged: {output_name} ({os.path.getsize(output_path):,} bytes)')

if __name__ == '__main__':
    for src, out, slug in PACKAGES:
        make_clean_zip(src, out, slug)
