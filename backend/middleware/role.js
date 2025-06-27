module.exports = function(requiredRoleId) {
  return function(req, res, next) {
    // Paksa keduanya jadi string, atau keduanya jadi integer
    if (req.user && String(req.user.role_id) === String(requiredRoleId)) {
      return next();
    }
    return res.status(403).json({ error: 'Forbidden: insufficient role' });
  };
};