const express = require('express');
const router = express.Router();
const prisma = require('../prismaClient');

// List inventory items
router.get('/', async (req, res) => {
  const items = await prisma.inventory.findMany({ include: { product: true } });
  res.json(items);
});

// Smart substitutes: find products with same activeIngredient
router.get('/substitutes/:productId', async (req, res) => {
  const id = parseInt(req.params.productId);
  const prod = await prisma.product.findUnique({ where: { id } });
  if (!prod) return res.status(404).json({ error: 'not found' });
  const subs = await prisma.product.findMany({
    where: { activeIngredient: prod.activeIngredient, id: { not: id } },
    orderBy: { price: 'asc' }
  });
  res.json(subs);
});

// Auto PO suggestion (very simple AI placeholder)
router.post('/auto-po', async (req, res) => {
  const { productId } = req.body;
  const inv = await prisma.inventory.findUnique({ where: { productId } });
  if (!inv) return res.status(404).json({ error: 'inventory not found' });
  // naive forecast: request enough to reach reorderLevel + 20
  const suggested = Math.max(0, inv.reorderLevel + 20 - inv.quantity);
  res.json({ productId, suggestedQty: suggested });
});

module.exports = router;
