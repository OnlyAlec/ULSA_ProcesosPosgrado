const fs = require('fs');
const path = require('path');
const { ChartJSNodeCanvas } = require('chartjs-node-canvas');

// Argumentos: archivo JSON, tipo, carpeta de salida
const args = process.argv.slice(2);
const filePath = args[0];
const type = args[1];
const outputDir = args[2] || __dirname;

fs.readFile(filePath, 'utf-8', async (err, data) => {
    if (err) throw err;

    const dataset = JSON.parse(data);

    const width = 800;
    const height = 600;
    const chartJSNodeCanvas = new ChartJSNodeCanvas({ width, height });

    const labels = Object.keys(dataset);
    const partialData = labels.map(k => dataset[k].partial);
    const totalData = labels.map(k => dataset[k].total);

    const config = {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Parciales',
                    data: partialData,
                    backgroundColor: 'rgba(211, 31, 31, 0.88)',
                },
                {
                    label: 'Totales',
                    data: totalData,
                    backgroundColor: 'rgba(90, 211, 255, 0.6)',
                },
            ],
        },
        options: {
            plugins: {
                title: {
                    display: true,
                    text: `Gráfica de ${type}`,
                    font: { size: 20 },
                },
            },
        },
    };

    const imageBuffer = await chartJSNodeCanvas.renderToBuffer(config);

    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    const imagePath = path.join(outputDir, `chart_${type}.png`);
    fs.writeFileSync(imagePath, imageBuffer);

    fs.unlink(filePath, (err) => {
        if (err) console.error('Error al eliminar el archivo temporal:', err);
        else console.log('Archivo temporal eliminado');
    });
});
