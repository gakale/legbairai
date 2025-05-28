// resources/js/components/common/Button.jsx
import React from 'react';
import { Link } from 'react-router-dom';

/**
 * Composant Bouton réutilisable.
 *
 * @param {object} props
 * @param {React.ReactNode} props.children - Le contenu du bouton (texte, icône, etc.).
 * @param {'primary' | 'secondary' | 'custom'} [props.variant='primary'] - La variante de style du bouton.
 * @param {'button' | 'submit' | 'reset'} [props.type='button'] - Le type HTML du bouton (si `as="button"`).
 * @param {string} [props.to] - Si fourni, le bouton agira comme un lien React Router.
 * @param {string} [props.href] - Si fourni (et `to` n'est pas fourni), le bouton agira comme un lien `<a>` standard.
 * @param {() => void} [props.onClick] - Fonction à appeler lors du clic.
 * @param {string} [props.className] - Classes Tailwind supplémentaires à appliquer.
 * @param {boolean} [props.disabled=false] - Désactive le bouton.
 * @param {React.ElementType} [props.as='button'] - L'élément HTML à rendre (button, a, Link).
 * @param {React.ReactNode} [props.iconLeft] - Icône à afficher à gauche du texte.
 * @param {React.ReactNode} [props.iconRight] - Icône à afficher à droite du texte.
 */
const Button = ({
    children,
    variant = 'primary',
    type = 'button',
    to,
    href,
    onClick,
    className = '',
    disabled = false,
    as: Component = 'button', // Par défaut, c'est un <button>
    iconLeft,
    iconRight,
    ...rest // Pour passer d'autres props HTML natives (ex: aria-label)
}) => {
    const baseClasses = "py-[0.7rem] px-[1.5rem] rounded-button font-semibold cursor-pointer transition-all duration-300 no-underline inline-flex items-center justify-center relative overflow-hidden text-center disabled:opacity-50 disabled:cursor-not-allowed";

    let variantClasses = '';
    switch (variant) {
        case 'primary':
            variantClasses = "bg-gb-gradient-1 text-gb-white shadow-gb-light hover:translate-y-[-2px] hover:shadow-gb-medium focus:ring-2 focus:ring-gb-primary-light focus:ring-opacity-50";
            break;
        case 'secondary':
            variantClasses = "bg-transparent text-gb-white border-2 border-gb-primary-light hover:bg-gb-primary-light hover:text-gb-dark hover:translate-y-[-2px] focus:ring-2 focus:ring-gb-primary-light focus:ring-opacity-50";
            break;
        case 'custom': // Pour les cas où on veut juste les classes de base + des classes custom
            variantClasses = "";
            break;
        default:
            variantClasses = "bg-gb-gradient-1 text-gb-white shadow-gb-light hover:translate-y-[-2px] hover:shadow-gb-medium";
    }

    const combinedClasses = `${baseClasses} ${variantClasses} ${className}`;

    const content = (
        <>
            {iconLeft && <span className="mr-2">{iconLeft}</span>}
            {children}
            {iconRight && <span className="ml-2">{iconRight}</span>}
        </>
    );

    if (to) { // Si 'to' est fourni, utiliser React Router Link
        return (
            <Link to={to} className={combinedClasses} {...rest}>
                {content}
            </Link>
        );
    }

    if (href) { // Si 'href' est fourni, utiliser une balise <a>
        return (
            <a href={href} className={combinedClasses} onClick={onClick} {...rest}>
                {content}
            </a>
        );
    }

    // Par défaut, ou si as="button"
    return (
        <button
            type={Component === 'button' ? type : undefined} // type seulement pertinent pour <button>
            className={combinedClasses}
            onClick={onClick}
            disabled={disabled}
            {...rest}
        >
            {content}
        </button>
    );
};

export default Button;