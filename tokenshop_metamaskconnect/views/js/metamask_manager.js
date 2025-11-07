// TokenShop MetamaskConnect - CORRIGÉ

let provider; // ← Déclaré globalement

document.addEventListener('DOMContentLoaded', async () => {
  console.log('[TokenShop] manager starting (final)');
  const connectBtn = document.getElementById('ts-connect-metamask');
  const statusDiv = document.getElementById('ts-wc-status');
  const addrSpan = document.getElementById('ts-wc-address');
  const disconnectBtn = document.getElementById('ts-wc-disconnect');
  const deleteBtn = document.getElementById('ts-wc-delete');

  function shortAddress(a) { return a ? `${a.slice(0,6)}…${a.slice(-4)}` : ''; }
  function isMobile() { return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent); }

  function getProvider() {
    if (typeof window.ethereum !== 'undefined') return window.ethereum;
    return null;
  }

  async function getChainId(p) {
    if (!p) return null;
    try {
      const hex = await p.request({ method: 'eth_chainId' });
      return parseInt(hex, 16);
    } catch (e) {
      console.warn('[TokenShop] getChainId failed', e);
      return null;
    }
  }

  async function loadStored() {
    try {
      const res = await fetch(ts_urls.get);
      const json = await res.json();
      if (json.success && json.wallet) {
        addrSpan.textContent = shortAddress(json.wallet.wallet_address);
        statusDiv.style.display = 'flex';
      } else {
        addrSpan.textContent = '';
        statusDiv.style.display = 'none';
      }
    } catch (e) {
      console.warn('[TokenShop] failed to load stored wallet', e);
    }
  }

  async function deleteWallet() {
    const choice = await Swal.fire({
      title: 'Supprimer votre wallet',
      html: 'Confirmez-vous la suppression de l\'adresse enregistrée pour ce compte ? Cette action est irréversible.',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer',
      cancelButtonText: 'Annuler'
    });
    if (!choice.isConfirmed) return;
    try {
      const res = await fetch(ts_urls.delete, { method: 'POST' });
      const json = await res.json();
      if (json.success) {
        Swal.fire('Supprimé', 'Votre adresse a été supprimée.', 'success');
        await loadStored();
      } else {
        Swal.fire('Erreur', json.error || 'Erreur suppression', 'error');
      }
    } catch (e) {
      console.error('[TokenShop] delete error', e);
      Swal.fire('Erreur', 'Impossible de supprimer votre adresse', 'error');
    }
  }

  // DÉPLACÉ HORS DE connectFlow
  async function handleAccountChange(newAccount, p) {
    const chainId = await getChainId(p);
    if (chainId !== 137) {
      Swal.fire({ icon: 'warning', title: 'Réseau non supporté', html: 'Seul le réseau <b>Polygon</b> (137) est autorisé.' });
      return;
    }

    const result = await Swal.fire({
      title: 'Nouveau compte détecté',
      html: `Un nouveau compte MetaMask a été détecté : <b>${shortAddress(newAccount)}</b>.<br/>Souhaitez-vous remplacer l'adresse enregistrée ?`,
      showCancelButton: true,
      confirmButtonText: 'Oui, remplacer',
      cancelButtonText: 'Non, conserver'
    });

    if (result.isConfirmed) {
      try {
        const res = await fetch(ts_urls.save, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ wallet_address: newAccount, network: chainId })
        });
        const json = await res.json();
        if (json.success) {
          Swal.fire('Mis à jour', 'L\'adresse a été mise à jour.', 'success');
          await loadStored();
        } else {
          Swal.fire('Erreur', json.error || 'Erreur', 'error');
        }
      } catch (e) {
        console.error('[TokenShop] save error', e);
        Swal.fire('Erreur', 'Impossible d\'enregistrer', 'error');
      }
    }
  }

  // DÉPLACÉ HORS DE connectFlow
  async function connectFlow() {
    provider = getProvider(); // ← OK, assigne au provider global

    if (!provider) {
      if (isMobile()) {
        Swal.fire({
          icon: 'info',
          title: 'Connexion mobile',
          html: 'Ouvrez ce site dans le navigateur MetaMask (Browser → URL).'
        });
        return;
      }
      Swal.fire('MetaMask non détecté', 'Installez MetaMask pour continuer.', 'warning');
      return;
    }

    try {
      const accounts = await provider.request({ method: 'eth_requestAccounts' });
      const account = accounts?.[0];
      if (!account) return;

      const chainId = await getChainId(provider);
      if (chainId !== 137) {
        Swal.fire({ icon: 'warning', title: 'Réseau non supporté', html: 'Seul Polygon (137) est autorisé.' });
        return;
      }

      await handleAccountChange(account, provider);
    } catch (err) {
      console.error('[TokenShop] connect error', err);
      Swal.fire('Erreur', 'Connexion annulée ou impossible.', 'error');
    }
  }

  function setupListeners(p) {
    if (!p || typeof p.on !== 'function') return;
    p.on('accountsChanged', (accounts) => {
      if (accounts?.length > 0) handleAccountChange(accounts[0], p);
    });
    p.on('chainChanged', (chainIdHex) => {
      const chain = parseInt(chainIdHex, 16);
      if (chain !== 137) {
        Swal.fire({ icon: 'warning', title: 'Réseau non supporté', html: 'Seul Polygon (137) est autorisé.' });
      }
    });
  }

  // Initialisation
  await loadStored();
  provider = getProvider();

  if (provider) {
    try {
      const accounts = await provider.request({ method: 'eth_accounts' });
      if (accounts?.length > 0) {
        console.log('[TokenShop] compte détecté au chargement');
      }
      setupListeners(provider);
    } catch (e) {
      console.warn('[TokenShop] initial check failed', e);
    }
  }

  connectBtn?.addEventListener('click', connectFlow);
  disconnectBtn?.addEventListener('click', () => {
    addrSpan.textContent = '';
    statusDiv.style.display = 'none';
    Swal.fire('Déconnecté', '', 'info');
  });
  deleteBtn?.addEventListener('click', deleteWallet);
});

