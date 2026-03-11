const XLSX = require('xlsx');

const files = [
    'EL - 2nd-Sem AY 2025-2026-List and Summary to sir Torres and BSCS Research Group.xlsx',
    'Official-List-of-Enrollment-2nd-Semester-AY-2025-2026.xlsx'
];

files.forEach(file => {
    console.log(`\n--- File: ${file} ---`);
    try {
        const workbook = XLSX.readFile(file);
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        
        // Read the first 15 rows to find headers
        const data = XLSX.utils.sheet_to_json(worksheet, { header: 1, range: 0, defval: null });
        let rowCount = 0;
        
        for (let i = 0; i < Math.min(15, data.length); i++) {
            const row = data[i];
            const nonNullCount = row.filter(cell => cell !== null && cell !== '').length;
            if (nonNullCount > 0) {
                console.log(`Row ${i} (non-null: ${nonNullCount}):`, row);
            }
        }
    } catch (e) {
        console.error(`Error reading ${file}:`, e.message);
    }
});
