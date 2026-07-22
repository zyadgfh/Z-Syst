const express = require('express');
const router = express.Router();
const prisma = require('../prismaClient');

// Create a sale
router.post('/sale', async (req, res) => {
  const { patientId, items } = req.body; // items: [{productId, qty}]
  if (!items || !items.length) return res.status(400).json({ error: 'no items' });

  // calculate total and create sale
  let total = 0;
  const txItems = [];
  for (const it of items) {
    const p = await prisma.product.findUnique({ where: { id: it.productId } });
    if (!p) return res.status(400).json({ error: 'product not found' });
    total += p.price * it.qty;
    txItems.push({ productId: it.productId, qty: it.qty, price: p.price });
  }

  const sale = await prisma.sale.create({ data: { patientId: patientId || null, total } });
  for (const it of txItems) {
    await prisma.saleItem.create({ data: { saleId: sale.id, productId: it.productId, qty: it.qty, price: it.price } });
    // decrement inventory
    const inv = await prisma.inventory.findUnique({ where: { productId: it.productId } });
    if (inv) {
      await prisma.inventory.update({ where: { id: inv.id }, data: { quantity: Math.max(0, inv.quantity - it.qty) } });
    }
  }

  res.json({ saleId: sale.id, total });
});

module.exports = router;
