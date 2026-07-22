const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const app = express();
const port = process.env.PORT || 4000;

app.use(cors());
app.use(bodyParser.json());

const posRoutes = require('./routes/pos');
const inventoryRoutes = require('./routes/inventory');

app.use('/api/pos', posRoutes);
app.use('/api/inventory', inventoryRoutes);

app.get('/api/health', (req, res) => res.json({ ok: true }));

app.listen(port, () => console.log(`PMS backend listening on ${port}`));
