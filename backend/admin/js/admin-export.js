/**
 * Multi-purpose HTML Table Export to CSV (Excel Compatible with Vietnamese BOM)
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert("Không tìm thấy dữ liệu bảng!");
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    for (let i = 0; i < rows.length; i++) {
        // Skip rows that are hidden (e.g. mobile search results or filter cards that are display:none)
        if (rows[i].offsetParent === null) continue;
        
        let row = [];
        const cols = rows[i].querySelectorAll("td, th");
        
        for (let j = 0; j < cols.length; j++) {
            // Skip the action cell / operation columns
            if (
                cols[j].classList.contains("actions-cell") || 
                cols[j].classList.contains("form-actions") ||
                cols[j].getAttribute("style") && cols[j].getAttribute("style").includes("width: 100px") ||
                cols[j].textContent.trim() === "Thao tác"
            ) {
                continue;
            }
            
            // Clean content: remove line breaks, quotes escaping
            let text = cols[j].innerText || cols[j].textContent;
            text = text.replace(/(\r\n|\n|\r)/gm, " ").trim();
            text = text.replace(/"/g, '""');
            
            row.push('"' + text + '"');
        }
        
        if (row.length > 0) {
            csv.push(row.join(","));
        }
    }
    
    // Create CSV string with UTF-8 BOM
    const csvContent = "\uFEFF" + csv.join("\n");
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    
    if (window.navigator && window.navigator.msSaveBlob) { // IE 10+
        window.navigator.msSaveBlob(blob, filename);
    } else {
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
