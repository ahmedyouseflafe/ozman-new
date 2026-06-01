const fs = require('fs');

function searchFile(path) {
    console.log(`--- Searching ${path} ---`);
    let content = '';
    try {
        const buf = fs.readFileSync(path);
        if (buf[0] === 0xff && buf[1] === 0xfe) {
            content = buf.toString('utf16le');
        } else {
            content = buf.toString('utf8');
        }
    } catch (e) {
        console.error(e);
        return;
    }

    const lines = content.split(/\r?\n/);
    lines.forEach((line, index) => {
        if (line.includes('mousedown') || line.includes('mousemove') || line.includes('mouseup') ||
            line.includes('touchstart') || line.includes('touchmove') || line.includes('touchend') ||
            line.includes('pointerdown') || line.includes('pointermove') || line.includes('pointerup')) {
            console.log(`Line ${index + 1}: ${line.trim()}`);
        }
    });
}

searchFile('c:\\Users\\HP\\Desktop\\newproject\\index.html');
searchFile('c:\\Users\\HP\\Desktop\\newproject\\style.css');
