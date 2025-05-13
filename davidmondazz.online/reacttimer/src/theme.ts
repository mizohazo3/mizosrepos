import { extendTheme, type ThemeConfig } from '@chakra-ui/react';

const config: ThemeConfig = {
  initialColorMode: 'light',
  useSystemColorMode: false,
};

const theme = extendTheme({
  config,
  styles: {
    global: (props: any) => ({
      body: {
        bg: props.colorMode === 'light' ? 'gray.50' : 'gray.900',
        color: props.colorMode === 'light' ? 'gray.800' : 'white',
        transition: 'all 0.2s ease-in-out',
      },
    }),
  },
  fonts: {
    heading: "'Inter', sans-serif",
    body: "'Inter', system-ui, sans-serif",
  },
  components: {
    Button: {
      baseStyle: {
        fontWeight: 'semibold',
        borderRadius: 'lg',
        transition: 'all 0.2s ease-in-out',
      },
      variants: {
        solid: (props: any) => ({
          bg: props.colorMode === 'light' ? 'blue.500' : 'blue.200',
          color: props.colorMode === 'light' ? 'white' : 'gray.800',
          _hover: {
            bg: props.colorMode === 'light' ? 'blue.600' : 'blue.300',
            transform: 'translateY(-2px)',
            boxShadow: 'lg',
          },
          _active: {
            bg: props.colorMode === 'light' ? 'blue.700' : 'blue.400',
            transform: 'translateY(0)',
          },
        }),
      },
    },
    Progress: {
      baseStyle: {
        track: {
          borderRadius: 'full',
          bg: 'gray.100',
        },
        filledTrack: {
          borderRadius: 'full',
          transition: 'all 0.3s ease-in-out',
        },
      },
    },
    Stat: {
      baseStyle: {
        container: {
          textAlign: 'center',
          transition: 'all 0.2s ease-in-out',
          _hover: {
            transform: 'translateY(-2px)',
          },
        },
        label: {
          color: 'gray.500',
          fontSize: 'sm',
          fontWeight: 'medium',
          textTransform: 'uppercase',
        },
        number: {
          fontSize: '2xl',
          fontWeight: 'bold',
          color: 'blue.500',
        },
        helpText: {
          color: 'gray.500',
          fontSize: 'xs',
        },
      },
    },
    Container: {
      baseStyle: {
        maxW: 'container.xl',
        px: { base: 4, md: 8 },
        py: { base: 4, md: 8 },
      },
    },
    Heading: {
      baseStyle: {
        fontWeight: 'bold',
        letterSpacing: 'tight',
      },
    },
    Input: {
      variants: {
        filled: {
          field: {
            borderRadius: 'lg',
            bg: 'gray.50',
            _hover: {
              bg: 'gray.100',
            },
            _focus: {
              bg: 'white',
              borderColor: 'blue.500',
            },
          },
        },
      },
    },
    Box: {
      variants: {
        card: (props: any) => ({
          p: 6,
          borderRadius: 'xl',
          bg: props.colorMode === 'light' ? 'white' : 'gray.800',
          boxShadow: 'lg',
          border: '1px solid',
          borderColor: props.colorMode === 'light' ? 'gray.200' : 'gray.700',
          transition: 'all 0.2s ease-in-out',
          _hover: {
            transform: 'translateY(-2px)',
            boxShadow: 'xl',
          },
        }),
      },
    },
  },
  layerStyles: {
    card: {
      p: 6,
      borderRadius: 'xl',
      boxShadow: 'lg',
      transition: 'all 0.2s ease-in-out',
    },
  },
  textStyles: {
    h1: {
      fontSize: ['4xl', '5xl'],
      fontWeight: 'bold',
      lineHeight: 'shorter',
      letterSpacing: 'tight',
    },
    h2: {
      fontSize: ['3xl', '4xl'],
      fontWeight: 'semibold',
      lineHeight: 'short',
      letterSpacing: 'tight',
    },
    subtitle: {
      fontSize: ['md', 'lg'],
      color: 'gray.500',
      lineHeight: 'base',
    },
  },
});

export default theme; 