const fs = require("fs").promises;
const path = require("path");
const { ChartJSNodeCanvas } = require("chartjs-node-canvas");

(async () => {
    try {
        // Argumentos: archivo JSON con los datos a graficar, tipo (maestría o especialidad), carpeta de salida de la imagen
        const args = process.argv.slice(2);
        const filePath = args[0];
        const type = args[1];
        const outputDir = args[2];

        const title = type === "maestrias" ? "Maestrías" : "Especialidades";

        // Lectura del archivo JSON
        const data = await fs.readFile(filePath, "utf-8");
        const dataset = JSON.parse(data);

        // Configuración de la gráfica
        const width = 800;
        const height = 600;
        const chartJSNodeCanvas = new ChartJSNodeCanvas({ width, height });

        const labels = Object.keys(dataset);
        const formattedLabels = labels.map((label) => {
            if (!label || typeof label !== "string") return ["(Sin nombre)"];
            const capitalizedLabel = label
                .trim()
                .split(" ")
                .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(" ");

            return capitalizedLabel.trim().split(/\s+/);
        });
        const partialData = labels.map((k) => dataset[k].partial);
        const totalData = labels.map((k) => dataset[k].total);

        const config = {
            type: "bar",
            data: {
                labels: formattedLabels,
                datasets: [
                    {
                        label: "Alumnos Sin Firmar",
                        data: partialData,
                        backgroundColor: "#001D68",
                    },
                    {
                        label: "Total de Alumnos",
                        data: totalData,
                        backgroundColor: "#D21034",
                    },
                ],
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: `Gráfica de ${title}`,
                        font: { size: 20 },
                    },
                },
                responsive: true,
                scales: {
                    x: {
                        stacked: false,
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0,
                            autoSkip: false,
                            font: { size: 12 },
                        },
                    },
                    y: {
                        beginAtZero: true,
                    },
                },
                barThickness: 40,
                categoryPercentage: 0.6,
                barPercentage: 0.9,
            },
        };

        const imageBuffer = await chartJSNodeCanvas.renderToBuffer(config);
        const imagePath = path.join(outputDir, `chart_${type}.png`);
        await fs.writeFile(imagePath, imageBuffer);

        // Eliminar archivo temporal
        await fs.unlink(filePath);
    } catch (err) {
        console.error("Error:", err.message);
        process.exit(1);
    }
})();
