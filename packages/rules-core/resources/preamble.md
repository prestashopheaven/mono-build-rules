# Conventies voor PrestaHaven-modules

Deze lijst is bindend voor alle code die de Module-Factory genereert. Elke regel heeft een ID
(bijv. `STR-03`), zodat je er in reviews en commits naar kunt verwijzen. Hernummer nooit.

**Dit bestand wordt gegenereerd; bewerk het niet met de hand.** De bron is het regelregister in
`libraries/mono-build-rules` (één JSON-bestand per sectie, met per regel de stages en hoe hij gecontroleerd wordt).
Nieuwe regel: voeg hem onderaan de juiste sectie in dat JSON-bestand toe met het volgende vrije nummer, en draai
`php libraries/mono-build-rules/packages/rules-core/bin/rules.php generate --out=.claude/skills/ps-module/conventions.md`.

Legenda: **[V]** = de PrestaShop-validator controleert dit (een fout hier betekent
afkeuring of een waarschuwing), **[T]** = een tool controleert dit (`composer check` /
`vendor/bin/ps-module`), zonder markering = review-regel.

Achtergrond en bronnen van veel regels (PrestaShop technical development standards, devdocs,
validator-changelog, populaire modules, multistore-gedrag van de core):
`research/technical-standards.md`.

---
