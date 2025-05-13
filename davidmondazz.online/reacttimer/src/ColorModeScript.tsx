import { ColorModeScript as ChakraColorModeScript } from '@chakra-ui/react';
import theme from './theme';

export const ColorModeScript = () => (
  <ChakraColorModeScript initialColorMode={theme.config.initialColorMode} />
); 