# HTML

### PONTOS POSITIVOS

- Estrutura ficou muito boa.
- Ótimo uso do title em páginas diferentes.
- Formulários estão bem completos, usou diferentes tipos de dados.
- Conteúdo da página teve ideias boas.

### SUGESTÕES DE MELHORIAS

- Poderia incluir mais meta tags

- Incluir imagens, elas estão sendo referenciadas mas não existem no projeto.

- Semanticamente, o ideal é utilizar `<a>` ao invés de `<button>` quando se usa links para redirecionar o usuário a uma seção diferente da página ou outra página. Por exemplo se esse for o caso dos botões de ver especialidades e agendar consulta.

- Poderia incluir h2 em algumas seções que usam `<p>` no título.

- Na section “numeros”, talvez não seja o mais ideal usar h3 com conteúdo de números, talvez uma tag strong ou similar fosse mais adequada aqui.

- Em alguns casos onde há imagem com descrição, poderia melhorar utilizando as funções de figure ao invés de div e p.

- Em alguns casos há uma tag span englobando a tag img. Se nao há mais nenhum conteúdo a englobar junto com a imagem, talvez essa tag span não faça sentido.

- Há alguns casos onde seções de mesmo nível possuem títulos de níveis diferentes, ou subtítulos usando a mesma tag h.

- Onde há uma lista de etapas, poderia usar listas ordenadas.

- Alguns locais há múltiplas divs uma dentro da outra mas sem conteúdo existente para ter a necessidade de tantos níveis. Por exemplo: `<div>   <div>   <div> conteudo… </div>   </div>   </div>`
A menos que há a ideia de algum tipo de estilização que requer essa estrutura.

- Uma sugestão que normalmente se usa em casos reais: o checkbox de aceite de termos normalmente inclui link, que leva à página de termos de uso, e um link que leva à página de política de privacidade.

- Cuidado com conteúdo repetido na página, há alguns lugares que repetem o mesmo conteúdo.



# CSS

### PONTOS POSITIVOS

- Uso de variáveis
- Uso de tipografia externa
- Uso de flexbox e grid com boa responsividade
- Customização de inputs
- Uso de animações

### SUGESTÕES DE MELHORIAS

- Conferir se a repsonsividade para dispositivos móveis está exibindo todos os elementos, incluindo menus de navegação que são essenciais.

- Cuidado com códigos duplicados, existe bastante CSS duplicado que poderia ser reusado, poderia incluir em arquivos de CSS globais/genéricos para reuso, inclusive o CSS que for usado em múltiplas páginas. A estrategia que usou em medico.css por exemplo contendo ali apenas estilos especificos necessarios sem replicar o resto, é interessante.

- Cuidado com style inline no HTML
