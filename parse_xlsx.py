import zipfile
import xml.etree.ElementTree as ET
import sys

def read_xlsx_headers(filepath, num_rows=10):
    try:
        with zipfile.ZipFile(filepath, 'r') as z:
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                ss_data = z.read('xl/sharedStrings.xml')
                root = ET.fromstring(ss_data)
                ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                for si in root.findall(f'{{{ns}}}si'):
                    t = si.find(f'{{{ns}}}t')
                    if t is not None:
                        shared_strings.append(t.text or "")
                    else:
                        texts = []
                        for r_t in si.findall(f'.//{{{ns}}}t'):
                            if r_t.text: texts.append(r_t.text)
                        shared_strings.append(''.join(texts))
            
            sheet_paths = [name for name in z.namelist() if name.startswith('xl/worksheets/sheet')]
            if not sheet_paths:
                print("No sheets found in", filepath)
                return
            
            sheet_data = z.read(sheet_paths[0])
            sheet_root = ET.fromstring(sheet_data)
            
            print(f"\n--- Output for {filepath} ---")
            count = 0
            ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            for row in sheet_root.findall(f'.//{{{ns}}}row'):
                row_data = []
                for c in row.findall(f'{{{ns}}}c'):
                    val_node = c.find(f'{{{ns}}}v')
                    val = val_node.text if val_node is not None else ""
                    
                    if c.attrib.get('t') == 's' and val:
                        val = shared_strings[int(val)]
                    row_data.append(val)
                
                if any(row_data):
                    print(f"Row {count}: {row_data}")
                    count += 1
                
                if count >= num_rows:
                    break
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

read_xlsx_headers('EL - 2nd-Sem AY 2025-2026-List and Summary to sir Torres and BSCS Research Group.xlsx', 15)
read_xlsx_headers('Official-List-of-Enrollment-2nd-Semester-AY-2025-2026.xlsx', 15)
