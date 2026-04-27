import sys
import os

path = r'c:\xampp\htdocs\importadorV2\pages\despesas.php'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

# Reconstruindo o CSS original Clean SaaS
original_css = """<style>
  /* ===== Clean SaaS: Despesas Corporativas ===== */
  .saas-head {
    border: 1px solid var(--saas-border);
    background: linear-gradient(135deg, rgba(13, 110, 253, .08), rgba(13, 110, 253, .02));
    border-radius: 18px;
    box-shadow: var(--saas-shadow-soft);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
  }

  html[data-theme="dark"] .saas-head {
    background: linear-gradient(135deg, rgba(13, 110, 253, .15), rgba(255, 255, 255, .02));
  }

  .saas-head:before {
    content: "";
    position: absolute;
    inset: -100px -150px auto auto;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle at 30% 30%, rgba(13, 110, 253, .2), transparent 60%);
    filter: blur(8px);
    transform: rotate(15deg);
    pointer-events: none;
  }

  .saas-title {
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--saas-text);
    margin: 0;
  }

  .saas-subtitle {
    margin: 6px 0 0;
    color: var(--saas-muted);
    font-size: 14px;
  }

  .metrics-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1.5rem;
    position: relative;
    z-index: 1;
  }

  .metric-card {
    flex: 1;
    min-width: 200px;
    background: var(--saas-surface);
    border: 1px solid var(--saas-border);
    border-radius: 16px;
    padding: 1rem 1.25rem;
    box-shadow: var(--saas-shadow-soft);
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
  }

  .metric-title {
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--saas-muted);
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .metric-value {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -.02em;
    color: var(--saas-text);
    margin-top: 4px;
    display: flex;
    align-items: baseline;
    gap: 6px;
  }
  
  .chip {
    padding: 6px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .chip-primary { background: rgba(13, 110, 253, .1); color: #0d6efd; }
  .chip-green { background: rgba(16, 185, 129, .1); color: #059669; }
  .chip-orange { background: rgba(245, 158, 11, .1); color: #d97706; }
  .chip-red { background: rgba(220, 53, 69, .1); color: #dc3545; }
</style>
"""

# Identificar o bloco que eu quero substituir
# No arquivo atual, a tag <style> começa na linha 10
# Eu vou substituir até a tag </script> que eu adicionei (linha 47 aprox)
# Mas é mais seguro reescrever o arquivo com as partes que eu sei que estão certas.

# Vou apenas re-injetar o CSS original e o HTML original nos blocos que eu mudei.
# No entanto, a forma mais rápida de desfazer é usar o REPLACE_FILE_CONTENT do próprio bot 
# com o conteúdo que eu recuperei.
print("Script finalizado")
